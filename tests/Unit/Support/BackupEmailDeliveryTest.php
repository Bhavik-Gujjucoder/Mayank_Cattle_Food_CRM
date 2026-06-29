<?php

use App\Mail\BackupCreatedMail;
use App\Models\SystemBackup;
use App\Support\BackupEmailDelivery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();

    DB::table('general_settings')->insert([
        ['key' => 'company_email', 'value' => '', 'created_at' => now(), 'updated_at' => now()],
    ]);
});

describe('queueCreated', function () {
    it('queues BackupCreatedMail to company email when set', function () {
        DB::table('general_settings')->where('key', 'company_email')->update(['value' => 'admin@company.test']);

        $backupDirectory = storage_path('app/private/backups');
        File::ensureDirectoryExists($backupDirectory);

        $filename = 'backup_test_'.uniqid().'.mcfbackup';
        $backupPath = $backupDirectory.DIRECTORY_SEPARATOR.$filename;
        file_put_contents($backupPath, 'encrypted-backup-test-content');

        $backup = SystemBackup::query()->create([
            'filename' => $filename,
            'file_size' => filesize($backupPath),
            'passphrase' => 'test-passphrase',
            'created_by' => null,
        ]);

        BackupEmailDelivery::queueCreated($backup);

        Mail::assertQueued(BackupCreatedMail::class, function (BackupCreatedMail $mail) use ($filename) {
            return $mail->backupFilename === $filename
                && $mail->hasTo('admin@company.test')
                && ($mail->payload['passphrase'] ?? '') === 'test-passphrase';
        });

        @unlink($backupPath);
    });

    it('does not queue mail when company email is not set', function () {
        $backupDirectory = storage_path('app/private/backups');
        File::ensureDirectoryExists($backupDirectory);

        $filename = 'backup_test_'.uniqid().'.mcfbackup';
        $backupPath = $backupDirectory.DIRECTORY_SEPARATOR.$filename;
        file_put_contents($backupPath, 'encrypted-backup-test-content');

        $backup = SystemBackup::query()->create([
            'filename' => $filename,
            'file_size' => filesize($backupPath),
            'passphrase' => 'test-passphrase',
            'created_by' => null,
        ]);

        BackupEmailDelivery::queueCreated($backup);

        Mail::assertNothingQueued();

        @unlink($backupPath);
    });
});
