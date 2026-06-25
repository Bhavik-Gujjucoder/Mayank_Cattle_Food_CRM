<?php

namespace App\Services\Backup;

use FilesystemIterator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use ZipArchive;

class BackupService
{
    public function __construct(
        private readonly ServerFingerprint $fingerprint,
        private readonly BackupCrypto $crypto,
        private readonly BackupManifest $manifest,
        private readonly DatabaseDumper $databaseDumper,
        private readonly DatabaseRestorer $databaseRestorer,
    ) {}

    public function ensureInitialized(): void
    {
        if ((string) config('backup.server_id') === '' || (string) config('backup.server_secret') === '') {
            throw new RuntimeException('Backup is not initialized. Run: php artisan backup:init');
        }

        $outputPath = (string) config('backup.output_path');
        if (! is_dir($outputPath) && ! mkdir($outputPath, 0755, true) && ! is_dir($outputPath)) {
            throw new RuntimeException("Unable to create backup directory: {$outputPath}");
        }
    }

    public function create(string $passphrase): string
    {
        $this->ensureInitialized();

        if (strlen($passphrase) < 8) {
            throw new RuntimeException('Passphrase must be at least 8 characters.');
        }

        $tempDirectory = $this->makeTempDirectory();

        try {
            $databaseSqlPath = $tempDirectory.'/database.sql';
            $this->databaseDumper->dump($databaseSqlPath);

            $publicTarget = $tempDirectory.'/files/public';
            $this->copyStoragePaths($publicTarget);

            $manifestData = [
                'database' => [
                    'path' => 'database.sql',
                    'size' => filesize($databaseSqlPath),
                    'checksum' => hash_file('sha256', $databaseSqlPath),
                ],
                'public' => $this->manifest->build($publicTarget),
            ];

            $manifestPath = $tempDirectory.'/manifest.json';
            $this->manifest->write($manifestPath, $manifestData);

            $innerZipPath = $tempDirectory.'/payload.zip';
            $this->createInnerArchive($tempDirectory, $innerZipPath);

            $innerPayload = (string) file_get_contents($innerZipPath);
            $encrypted = $this->crypto->encrypt($innerPayload, $passphrase);

            $header = [
                'version' => (int) config('backup.version'),
                'magic' => (string) config('backup.magic'),
                'created_at' => now()->toIso8601String(),
                'server_fingerprint' => $this->fingerprint->compute(),
                'payload_checksum' => hash('sha256', $innerPayload),
                'salt' => base64_encode($encrypted['salt']),
                'nonce' => base64_encode($encrypted['nonce']),
            ];

            $header['hmac'] = $this->crypto->signHeader($header);

            $outputFile = $this->buildOutputFilename();
            $this->writeBackupFile($outputFile, $header, $encrypted['ciphertext']);

            return $outputFile;
        } finally {
            File::deleteDirectory($tempDirectory);
        }
    }

    public function restore(string $backupPath, string $passphrase, bool $skipServerBinding = false): void
    {
        $this->ensureInitialized();

        if (! is_file($backupPath)) {
            throw new RuntimeException("Backup file not found: {$backupPath}");
        }

        [$header, $ciphertext] = $this->readBackupFile($backupPath);
        $this->crypto->verifyHeader($header);

        if (! $skipServerBinding && config('backup.bind_to_server') && ! $this->fingerprint->matches($header['server_fingerprint'])) {
            throw new RuntimeException('This backup cannot be restored on this server.');
        }

        $salt = base64_decode((string) $header['salt'], true);
        $nonce = base64_decode((string) $header['nonce'], true);

        if ($salt === false || $nonce === false) {
            throw new RuntimeException('Backup header contains invalid salt or nonce.');
        }

        $innerPayload = $this->crypto->decrypt($ciphertext, $passphrase, $salt, $nonce);

        if (! hash_equals((string) $header['payload_checksum'], hash('sha256', $innerPayload))) {
            throw new RuntimeException('Backup payload checksum mismatch. File may be corrupted.');
        }

        $tempDirectory = $this->makeTempDirectory();

        try {
            $innerZipPath = $tempDirectory.'/payload.zip';
            file_put_contents($innerZipPath, $innerPayload);
            $this->extractInnerArchive($innerZipPath, $tempDirectory);

            $manifestPath = $tempDirectory.'/manifest.json';
            $manifestData = $this->manifest->read($manifestPath);

            $databaseSqlPath = $tempDirectory.'/database.sql';
            if (! is_file($databaseSqlPath)) {
                throw new RuntimeException('Backup archive is missing database.sql');
            }

            $databaseMeta = $manifestData['database'] ?? null;
            if (is_array($databaseMeta)) {
                $this->assertFileMeta($databaseSqlPath, $databaseMeta, 'database.sql');
            }

            $publicSource = $tempDirectory.'/files/public';
            if (is_dir($publicSource) && isset($manifestData['public']) && is_array($manifestData['public'])) {
                $this->manifest->verify($publicSource, $manifestData['public']);
            }

            Artisan::call('down', ['--retry' => 60]);

            try {
                $this->databaseRestorer->restore($databaseSqlPath);
                $this->restoreStoragePaths($publicSource);
                $this->ensureStorageLink();
                Artisan::call('config:clear');
                Artisan::call('cache:clear');
            } finally {
                Artisan::call('up');
            }
        } finally {
            File::deleteDirectory($tempDirectory);
        }
    }

    /**
     * @return list<array{filename: string, path: string, size: int, modified_at: string}>
     */
    public function listBackups(): array
    {
        $outputPath = (string) config('backup.output_path');
        $extension = (string) config('backup.extension');

        if (! is_dir($outputPath)) {
            return [];
        }

        $files = glob($outputPath.'/*.'.$extension) ?: [];
        rsort($files);

        return array_map(function (string $path) {
            return [
                'filename' => basename($path),
                'path' => $path,
                'size' => (int) filesize($path),
                'modified_at' => date('Y-m-d H:i:s', (int) filemtime($path)),
            ];
        }, $files);
    }

    public function resolveBackupPath(string $filename): string
    {
        $filename = basename($filename);
        $extension = (string) config('backup.extension');

        if (! str_ends_with(strtolower($filename), '.'.$extension)) {
            throw new RuntimeException('Invalid backup file type.');
        }

        $outputPath = realpath((string) config('backup.output_path'));
        $backupPath = realpath($outputPath.DIRECTORY_SEPARATOR.$filename);

        if ($outputPath === false || $backupPath === false || ! is_file($backupPath)) {
            throw new RuntimeException('Backup file not found.');
        }

        if (! str_starts_with($backupPath, $outputPath)) {
            throw new RuntimeException('Invalid backup file path.');
        }

        return $backupPath;
    }

    public static function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 2).' KB';
        }

        return round($bytes / 1048576, 2).' MB';
    }

    private function buildOutputFilename(): string
    {
        $filename = 'backup_'.now()->format('Y-m-d_His').'.'.config('backup.extension');

        return rtrim((string) config('backup.output_path'), '\\/').DIRECTORY_SEPARATOR.$filename;
    }

    private function makeTempDirectory(): string
    {
        $path = storage_path('app/temp/backup_'.bin2hex(random_bytes(8)));

        if (! mkdir($path, 0755, true) && ! is_dir($path)) {
            throw new RuntimeException("Unable to create temp directory: {$path}");
        }

        return $path;
    }

    private function copyStoragePaths(string $publicTarget): void
    {
        foreach ((array) config('backup.storage_paths') as $sourcePath) {
            if (! is_dir($sourcePath)) {
                continue;
            }

            File::copyDirectory($sourcePath, $publicTarget);
        }
    }

    private function createInnerArchive(string $tempDirectory, string $innerZipPath): void
    {
        $zip = new ZipArchive();

        if ($zip->open($innerZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create backup archive.');
        }

        $zip->addFile($tempDirectory.'/database.sql', 'database.sql');
        $zip->addFile($tempDirectory.'/manifest.json', 'manifest.json');

        $publicSource = $tempDirectory.'/files/public';
        if (is_dir($publicSource)) {
            $this->addDirectoryToZip($zip, $publicSource, 'files/public');
        }

        $zip->close();
    }

    private function addDirectoryToZip(ZipArchive $zip, string $directory, string $zipPrefix): void
    {
        $directory = rtrim(str_replace('\\', '/', $directory), '/');
        $zipPrefix = trim(str_replace('\\', '/', $zipPrefix), '/');

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $absolutePath = str_replace('\\', '/', $file->getPathname());
            $relativePath = ltrim(substr($absolutePath, strlen($directory)), '/');
            $zipPath = $zipPrefix.'/'.$relativePath;

            $zip->addFile($file->getPathname(), $zipPath);
        }
    }

    private function extractInnerArchive(string $innerZipPath, string $targetDirectory): void
    {
        $zip = new ZipArchive();

        if ($zip->open($innerZipPath) !== true) {
            throw new RuntimeException('Unable to open backup archive.');
        }

        if (! $zip->extractTo($targetDirectory)) {
            $zip->close();
            throw new RuntimeException('Unable to extract backup archive.');
        }

        $zip->close();
    }

    /**
     * @param  array<string, mixed>  $header
     */
    private function writeBackupFile(string $outputFile, array $header, string $ciphertext): void
    {
        $magic = (string) config('backup.magic');
        $headerJson = json_encode($header, JSON_THROW_ON_ERROR);
        $headerLength = pack('N', strlen($headerJson));

        $contents = $magic.$headerLength.$headerJson.$ciphertext;
        file_put_contents($outputFile, $contents);
    }

    /**
     * @return array{0: array<string, mixed>, 1: string}
     */
    private function readBackupFile(string $backupPath): array
    {
        $contents = (string) file_get_contents($backupPath);
        $magic = (string) config('backup.magic');
        $magicLength = strlen($magic);

        if (! str_starts_with($contents, $magic)) {
            throw new RuntimeException('Invalid backup file format.');
        }

        $lengthBinary = substr($contents, $magicLength, 4);
        if (strlen($lengthBinary) !== 4) {
            throw new RuntimeException('Invalid backup file header.');
        }

        $headerLength = unpack('N', $lengthBinary)[1];
        $headerJson = substr($contents, $magicLength + 4, $headerLength);
        $ciphertext = substr($contents, $magicLength + 4 + $headerLength);

        if ($headerJson === false || $ciphertext === false || $headerJson === '') {
            throw new RuntimeException('Backup file is corrupted.');
        }

        $header = json_decode($headerJson, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($header)) {
            throw new RuntimeException('Backup header is invalid.');
        }

        return [$header, $ciphertext];
    }

    private function restoreStoragePaths(string $publicSource): void
    {
        foreach ((array) config('backup.storage_paths') as $targetPath) {
            $this->clearDirectoryPreservingGitignore($targetPath);

            if (is_dir($publicSource)) {
                File::copyDirectory($publicSource, $targetPath);
            }
        }
    }

    private function clearDirectoryPreservingGitignore(string $directory): void
    {
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);

            return;
        }

        foreach (scandir($directory) ?: [] as $item) {
            if (in_array($item, ['.', '..', '.gitignore'], true)) {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$item;
            if (is_dir($path)) {
                File::deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
    }

    private function ensureStorageLink(): void
    {
        $link = public_path('storage');
        $target = storage_path('app/public');

        if (! file_exists($link)) {
            Artisan::call('storage:link');
        }
    }

    /**
     * @param  array{path?: string, size?: int, checksum?: string}  $meta
     */
    private function assertFileMeta(string $filePath, array $meta, string $label): void
    {
        $size = filesize($filePath);
        $checksum = hash_file('sha256', $filePath);

        if ((int) $size !== (int) ($meta['size'] ?? -1) || ! hash_equals((string) ($meta['checksum'] ?? ''), $checksum)) {
            throw new RuntimeException("Manifest verification failed for {$label}.");
        }
    }
}
