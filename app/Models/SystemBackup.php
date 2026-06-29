<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class SystemBackup extends Model
{
    protected $fillable = [
        'filename',
        'file_size',
        'passphrase',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function storagePath(): string
    {
        return rtrim((string) config('backup.output_path'), '\\/').DIRECTORY_SEPARATOR.$this->filename;
    }

    public function fileExists(): bool
    {
        return is_file($this->storagePath());
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return list<array{filename: string, file_size: int, passphrase: ?string, created_by: ?int, created_at: ?string, updated_at: ?string}>
     */
    public static function captureForRestore(): array
    {
        if (! Schema::hasTable('system_backups')) {
            return [];
        }

        return static::query()
            ->orderBy('id')
            ->get()
            ->map(fn (self $backup) => [
                'filename' => $backup->filename,
                'file_size' => $backup->file_size,
                'passphrase' => $backup->passphrase,
                'created_by' => $backup->created_by,
                'created_at' => $backup->created_at?->toDateTimeString(),
                'updated_at' => $backup->updated_at?->toDateTimeString(),
            ])
            ->all();
    }

    /**
     * @param  list<array{filename: string, file_size: int, passphrase: ?string, created_by: ?int, created_at: ?string, updated_at: ?string}>  $records
     */
    public static function reapplyAfterRestore(array $records): void
    {
        if ($records === [] || ! Schema::hasTable('system_backups')) {
            return;
        }

        static::query()->delete();

        foreach ($records as $row) {
            $createdBy = $row['created_by'] ?? null;

            if ($createdBy && ! User::query()->whereKey($createdBy)->exists()) {
                $createdBy = null;
            }

            static::query()->create([
                'filename' => $row['filename'],
                'file_size' => $row['file_size'],
                'passphrase' => $row['passphrase'],
                'created_by' => $createdBy,
                'created_at' => $row['created_at'] ?? now(),
                'updated_at' => $row['updated_at'] ?? now(),
            ]);
        }
    }
}
