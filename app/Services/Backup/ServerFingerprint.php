<?php

namespace App\Services\Backup;

use RuntimeException;

class ServerFingerprint
{
    public function compute(): string
    {
        $serverId = (string) config('backup.server_id');

        if ($serverId === '') {
            throw new RuntimeException('BACKUP_SERVER_ID is not configured. Run: php artisan backup:init');
        }

        $connection = config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        $parts = [
            $serverId,
            php_uname('n'),
            realpath(base_path()) ?: base_path(),
            $connection,
            $database,
        ];

        return hash('sha256', implode('|', $parts));
    }

    public function matches(string $fingerprint): bool
    {
        return hash_equals($this->compute(), $fingerprint);
    }
}
