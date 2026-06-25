<?php

namespace App\Services\Backup;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

class BackupManifest
{
    /**
     * @return array{generated_at: string, entries: list<array{path: string, size: int, checksum: string}>}
     */
    public function build(string $rootDirectory): array
    {
        $rootDirectory = rtrim(str_replace('\\', '/', $rootDirectory), '/');

        if (! is_dir($rootDirectory)) {
            return [
                'generated_at' => now()->toIso8601String(),
                'entries' => [],
            ];
        }

        $entries = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootDirectory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $absolutePath = str_replace('\\', '/', $file->getPathname());
            $relativePath = ltrim(substr($absolutePath, strlen($rootDirectory)), '/');

            $entries[] = [
                'path' => $relativePath,
                'size' => $file->getSize(),
                'checksum' => hash_file('sha256', $file->getPathname()),
            ];
        }

        usort($entries, fn (array $a, array $b) => strcmp($a['path'], $b['path']));

        return [
            'generated_at' => now()->toIso8601String(),
            'entries' => $entries,
        ];
    }

    /**
     * @param  array{generated_at?: string, entries?: list<array{path: string, size: int, checksum: string}>}  $manifest
     */
    public function verify(string $rootDirectory, array $manifest): void
    {
        $rootDirectory = rtrim(str_replace('\\', '/', $rootDirectory), '/');
        $entries = $manifest['entries'] ?? [];

        foreach ($entries as $entry) {
            $filePath = $rootDirectory.'/'.$entry['path'];

            if (! is_file($filePath)) {
                throw new RuntimeException("Manifest verification failed. Missing file: {$entry['path']}");
            }

            $size = filesize($filePath);
            $checksum = hash_file('sha256', $filePath);

            if ((int) $size !== (int) $entry['size'] || ! hash_equals($entry['checksum'], $checksum)) {
                throw new RuntimeException("Manifest verification failed. Checksum mismatch: {$entry['path']}");
            }
        }
    }

    public function write(string $path, array $manifest): void
    {
        $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        file_put_contents($path, $json);
    }

    public function read(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("Manifest not found: {$path}");
        }

        $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            throw new RuntimeException('Manifest file is invalid.');
        }

        return $decoded;
    }
}
