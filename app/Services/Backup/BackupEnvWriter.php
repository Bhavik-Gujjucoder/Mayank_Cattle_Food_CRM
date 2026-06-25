<?php

namespace App\Services\Backup;

use Illuminate\Support\Str;
use RuntimeException;

class BackupEnvWriter
{
    public function isInitialized(): bool
    {
        return (string) config('backup.server_id') !== ''
            && (string) config('backup.server_secret') !== '';
    }

    /**
     * @return array{server_id: string, server_secret: string}
     */
    public function initialize(bool $force = false): array
    {
        if ($this->isInitialized() && ! $force) {
            throw new RuntimeException('Backup keys already exist. Use --force to regenerate (this will invalidate existing backups).');
        }

        $serverId = (string) Str::uuid();
        $serverSecret = Str::random(64);

        $this->setEnvValue('BACKUP_SERVER_ID', $serverId);
        $this->setEnvValue('BACKUP_SERVER_SECRET', $serverSecret);
        $this->setEnvValue('BACKUP_MYSQLDUMP_PATH', 'D:/xampp/mysql/bin/mysqldump.exe', false);
        $this->setEnvValue('BACKUP_MYSQL_PATH', 'D:/xampp/mysql/bin/mysql.exe', false);

        $outputPath = storage_path('app/private/backups');
        if (! is_dir($outputPath) && ! mkdir($outputPath, 0755, true) && ! is_dir($outputPath)) {
            throw new RuntimeException("Unable to create backup directory: {$outputPath}");
        }

        return [
            'server_id' => $serverId,
            'server_secret' => $serverSecret,
        ];
    }

    private function setEnvValue(string $key, string $value, bool $overwrite = true): void
    {
        $envPath = base_path('.env');

        if (! is_file($envPath)) {
            throw new RuntimeException('.env file not found.');
        }

        $contents = (string) file_get_contents($envPath);
        $pattern = '/^'.preg_quote($key, '/').'=.*$/m';
        $line = $key.'='.$this->quoteEnvValue($value);

        if (preg_match($pattern, $contents)) {
            if ($overwrite) {
                $contents = preg_replace($pattern, $line, $contents) ?? $contents;
            }
        } else {
            $contents = rtrim($contents).PHP_EOL.$line.PHP_EOL;
        }

        file_put_contents($envPath, $contents);
    }

    private function quoteEnvValue(string $value): string
    {
        if ($value === '' || preg_match('/[\s#="\'\\\\]/', $value)) {
            return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
        }

        return $value;
    }
}
