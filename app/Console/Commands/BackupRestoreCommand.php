<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupService;
use Illuminate\Console\Command;

class BackupRestoreCommand extends Command
{
    protected $signature = 'backup:restore
                            {file : Path to the .mcfbackup file}
                            {--passphrase= : Backup passphrase}
                            {--force : Skip confirmation prompt}
                            {--ignore-server-binding : Allow restore even if server fingerprint differs (local testing only)}';

    protected $description = 'Restore database and storage/app/public from an encrypted backup';

    public function handle(BackupService $backupService): int
    {
        $file = (string) $this->argument('file');

        if (! is_file($file)) {
            $configuredPath = rtrim((string) config('backup.output_path'), '\\/').DIRECTORY_SEPARATOR.$file;
            if (is_file($configuredPath)) {
                $file = $configuredPath;
            }
        }

        if (! $this->option('force')) {
            $this->warn('This will overwrite your current database and storage/app/public files.');

            if (! $this->confirm('Do you want to continue?', false)) {
                $this->info('Restore cancelled.');

                return self::SUCCESS;
            }
        }

        $passphrase = (string) ($this->option('passphrase') ?? '');

        if ($passphrase === '') {
            $passphrase = (string) $this->secret('Enter backup passphrase');
        }

        try {
            $this->info('Restoring backup...');
            $backupService->restore(
                $file,
                $passphrase,
                (bool) $this->option('ignore-server-binding')
            );
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Backup restored successfully.');

        return self::SUCCESS;
    }
}
