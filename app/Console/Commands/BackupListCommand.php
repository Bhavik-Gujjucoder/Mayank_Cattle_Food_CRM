<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupService;
use Illuminate\Console\Command;

class BackupListCommand extends Command
{
    protected $signature = 'backup:list';

    protected $description = 'List encrypted backups stored on this server';

    public function handle(BackupService $backupService): int
    {
        $backups = $backupService->listBackups();

        if ($backups === []) {
            $this->info('No backups found in '.config('backup.output_path'));

            return self::SUCCESS;
        }

        $this->table(
            ['Filename', 'Size', 'Modified At', 'Path'],
            array_map(function (array $backup) {
                return [
                    $backup['filename'],
                    $this->formatBytes($backup['size']),
                    $backup['modified_at'],
                    $backup['path'],
                ];
            }, $backups)
        );

        return self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 2).' KB';
        }

        return round($bytes / 1048576, 2).' MB';
    }
}
