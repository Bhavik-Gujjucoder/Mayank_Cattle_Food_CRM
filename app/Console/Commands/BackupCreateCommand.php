<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupService;
use Illuminate\Console\Command;

class BackupCreateCommand extends Command
{
    protected $signature = 'backup:create
                            {--passphrase= : Backup passphrase (min 8 characters)}';

    protected $description = 'Create an encrypted backup of the database and storage/app/public';

    public function handle(BackupService $backupService): int
    {
        $passphrase = (string) ($this->option('passphrase') ?? '');

        if ($passphrase === '') {
            $passphrase = (string) $this->secret('Enter backup passphrase (min 8 characters)');
            $confirmation = (string) $this->secret('Confirm backup passphrase');

            if ($passphrase !== $confirmation) {
                $this->error('Passphrases do not match.');

                return self::FAILURE;
            }
        }

        try {
            $this->info('Creating backup...');
            $outputFile = $backupService->create($passphrase);
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Backup created successfully.');
        $this->line($outputFile);
        $this->warn('Store your passphrase safely. Backups cannot be restored without it.');

        return self::SUCCESS;
    }
}
