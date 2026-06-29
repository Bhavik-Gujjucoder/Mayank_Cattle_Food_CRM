<?php

namespace App\Support;

use App\Mail\BackupCreatedMail;
use App\Models\SystemBackup;
use App\Services\Backup\BackupService;

class BackupEmailDelivery
{
    public static function queueCreated(SystemBackup $backup): void
    {
        $companyEmail = trim(getSetting('company_email'));

        if ($companyEmail === '') {
            return;
        }

        $backup->loadMissing('creator');

        if (! $backup->fileExists()) {
            return;
        }

        $payload = [
            'filename' => $backup->filename,
            'passphrase' => $backup->passphrase ?? '',
            'size_label' => BackupService::formatBytes($backup->file_size),
            'created_at' => $backup->created_at?->format('Y-m-d H:i:s') ?? '',
            'created_by_name' => $backup->creator?->name ?? '—',
        ];

        EmailDelivery::queue(
            $companyEmail,
            new BackupCreatedMail(
                backupFilename: $backup->filename,
                backupPath: $backup->storagePath(),
                payload: $payload,
            ),
        );
    }
}
