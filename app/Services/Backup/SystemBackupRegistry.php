<?php

namespace App\Services\Backup;

use App\Models\SystemBackup;

class SystemBackupRegistry
{
    public function __construct(
        private readonly BackupService $backupService,
    ) {}

    public function record(string $backupPath, string $passphrase, ?int $userId): SystemBackup
    {
        return SystemBackup::query()->updateOrCreate(
            ['filename' => basename($backupPath)],
            [
                'file_size' => (int) filesize($backupPath),
                'passphrase' => $passphrase,
                'created_by' => $userId,
            ]
        );
    }

    public function syncFilesystemBackups(): void
    {
        foreach ($this->backupService->listBackups() as $backup) {
            SystemBackup::query()->firstOrCreate(
                ['filename' => $backup['filename']],
                [
                    'file_size' => $backup['size'],
                    'passphrase' => null,
                    'created_by' => null,
                ]
            );
        }
    }

    /**
     * @return list<array{filename: string, size: int, size_label: string, modified_at: string, download_url: string}>
     */
    public function optionsPayload(): array
    {
        $this->syncFilesystemBackups();

        return SystemBackup::query()
            ->orderByDesc('id')
            ->get()
            ->filter(fn (SystemBackup $backup) => $backup->fileExists())
            ->map(fn (SystemBackup $backup) => $this->formatRow($backup))
            ->values()
            ->all();
    }

    /**
     * @return array{filename: string, size: int, size_label: string, modified_at: string, sort_at: int, download_url: string, created_by_name: string}
     */
    public function formatRow(SystemBackup $backup): array
    {
        return [
            'filename' => $backup->filename,
            'size' => $backup->file_size,
            'size_label' => BackupService::formatBytes($backup->file_size),
            'modified_at' => $backup->created_at?->format('Y-m-d H:i:s') ?? '',
            'sort_at' => $backup->created_at?->timestamp ?? 0,
            'download_url' => route('system.backup.download', $backup->filename),
            'created_by_name' => $backup->creator?->name ?? '—',
        ];
    }

    public function datatableQuery()
    {
        $this->syncFilesystemBackups();

        return SystemBackup::query()
            ->with('creator')
            ->orderByDesc('id');
    }
}
