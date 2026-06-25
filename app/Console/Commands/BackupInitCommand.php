<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupEnvWriter;
use Illuminate\Console\Command;

class BackupInitCommand extends Command
{
    protected $signature = 'backup:init {--force : Regenerate backup keys (invalidates existing backups)}';

    protected $description = 'Generate BACKUP_SERVER_ID and BACKUP_SERVER_SECRET for encrypted backups';

    public function handle(BackupEnvWriter $envWriter): int
    {
        try {
            $keys = $envWriter->initialize((bool) $this->option('force'));
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Backup encryption keys generated successfully.');
        $this->line('BACKUP_SERVER_ID='.$keys['server_id']);
        $this->warn('BACKUP_SERVER_SECRET was written to .env — keep it secret and do not commit it.');
        $this->newLine();
        $this->line('Run: php artisan config:clear');
        $this->line('Then: php artisan backup:create');

        return self::SUCCESS;
    }
}
