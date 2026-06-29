<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateBackupRequest;
use App\Http\Requests\RestoreBackupRequest;
use App\Models\SystemBackup;
use App\Services\Backup\BackupEnvWriter;
use App\Services\Backup\BackupService;
use App\Services\Backup\SystemBackupRegistry;
use App\Support\BackupEmailDelivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\DataTables;

class SystemBackupController extends Controller
{
    public function index(BackupEnvWriter $envWriter): View
    {
        return view('system.backup.index', [
            'page_title' => 'System Backup',
            'initialized' => $envWriter->isInitialized(),
        ]);
    }

    public function list(Request $request, SystemBackupRegistry $registry, BackupEnvWriter $envWriter): JsonResponse
    {
        if ($request->query('for') === 'options') {
            return response()->json([
                'success' => true,
                'initialized' => $envWriter->isInitialized(),
                'backups' => $envWriter->isInitialized() ? $registry->optionsPayload() : [],
            ]);
        }

        if (! $envWriter->isInitialized()) {
            return DataTables::of(collect())->make(true);
        }

        return DataTables::of($registry->datatableQuery())
            ->addIndexColumn()
            ->addColumn('size_label', fn (SystemBackup $backup) => BackupService::formatBytes($backup->file_size))
            ->addColumn('modified_at', fn (SystemBackup $backup) => $backup->created_at?->format('Y-m-d H:i:s') ?? '—')
            ->addColumn('created_by_name', fn (SystemBackup $backup) => e($backup->creator?->name ?? '—'))
            ->addColumn('action', function (SystemBackup $backup) {
                $downloadButton = $backup->fileExists()
                    ? '<a href="'.e(route('system.backup.download', $backup->filename)).'" class="btn btn-sm btn-outline-primary me-1">'
                        .'<i class="ti ti-download"></i> Download</a>'
                    : '<span class="text-muted me-2">File missing</span>';

                return $downloadButton
                    .'<button type="button" class="btn btn-sm btn-outline-danger delete-backup-btn" '
                    .'data-id="'.$backup->id.'" data-filename="'.e($backup->filename).'">'
                    .'<i class="ti ti-trash"></i> Delete</button>';
            })
            ->filterColumn('filename', function ($query, $keyword) {
                $query->where('filename', 'like', '%'.$keyword.'%');
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function initialize(BackupEnvWriter $envWriter): JsonResponse
    {
        try {
            $envWriter->initialize();
            Artisan::call('config:clear');
        } catch (\Throwable $exception) {
            return $this->errorResponse($exception->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Backup system initialized. Encryption keys were saved to .env.',
            'initialized' => true,
            'backups' => [],
        ]);
    }

    public function create(
        CreateBackupRequest $request,
        BackupService $backupService,
        SystemBackupRegistry $registry
    ): JsonResponse {
        $passphrase = $request->string('create_passphrase')->toString();

        try {
            $backupPath = $backupService->create($passphrase);
            $record = $registry->record($backupPath, $passphrase, Auth::id());
            BackupEmailDelivery::queueCreated($record->load('creator'));
        } catch (\Throwable $exception) {
            return $this->errorResponse($exception->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Backup created successfully: '.$record->filename,
            'backup' => $registry->formatRow($record->load('creator')),
        ]);
    }

    public function download(string $filename, BackupService $backupService): BinaryFileResponse|JsonResponse
    {
        try {
            $path = $this->resolveDownloadPath($filename, $backupService);
        } catch (\Throwable $exception) {
            return $this->errorResponse($exception->getMessage(), 404);
        }

        return response()->download($path, basename($path));
    }

    public function destroy(SystemBackup $backup): JsonResponse
    {
        try {
            if ($backup->fileExists()) {
                unlink($backup->storagePath());
            }

            $backup->delete();
        } catch (\Throwable $exception) {
            return $this->errorResponse($exception->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Backup deleted successfully.',
        ]);
    }

    public function restore(RestoreBackupRequest $request, BackupService $backupService): JsonResponse
    {
        if (! Auth::guard('web')->validate([
            'email' => $request->user()->email,
            'password' => $request->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $tempDirectory = null;

        try {
            $backupPath = $this->resolveRestorePath($request, $backupService, $tempDirectory);

            $backupService->restore(
                $backupPath,
                $this->resolveRestorePassphrase($request)
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            return $this->errorResponse($exception->getMessage());
        } finally {
            if ($tempDirectory !== null && is_dir($tempDirectory)) {
                File::deleteDirectory($tempDirectory);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Backup restored successfully. Database and storage files were replaced.',
        ]);
    }

    private function errorResponse(string $message, int $status = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    private function resolveDownloadPath(string $filename, BackupService $backupService): string
    {
        return $backupService->resolveBackupPath($filename);
    }

    private function resolveRestorePath(
        RestoreBackupRequest $request,
        BackupService $backupService,
        ?string &$tempDirectory
    ): string {
        if ($request->input('restore_source') === 'server') {
            return $this->resolveDownloadPath((string) $request->input('backup_filename'), $backupService);
        }

        $uploadedFile = $request->file('backup_file');

        if ($uploadedFile === null) {
            throw ValidationException::withMessages([
                'backup_file' => 'Please upload a backup file.',
            ]);
        }

        $tempDirectory = storage_path('app/temp/restore_upload_'.bin2hex(random_bytes(8)));

        if (! mkdir($tempDirectory, 0755, true) && ! is_dir($tempDirectory)) {
            throw new \RuntimeException('Unable to prepare uploaded backup file.');
        }

        $destination = $tempDirectory.DIRECTORY_SEPARATOR.$uploadedFile->getClientOriginalName();
        $uploadedFile->move($tempDirectory, basename($destination));

        return $destination;
    }

    private function resolveRestorePassphrase(RestoreBackupRequest $request): string
    {
        if ($request->input('restore_source') === 'server') {
            $filename = basename((string) $request->input('backup_filename'));
            $record = SystemBackup::query()->where('filename', $filename)->first();

            if ($record && filled($record->passphrase)) {
                return $record->passphrase;
            }

            throw ValidationException::withMessages([
                'backup_filename' => 'No passphrase is stored for this backup. Upload the file instead and enter the passphrase manually.',
            ]);
        }

        $passphrase = (string) $request->input('restore_passphrase', '');

        if ($passphrase === '') {
            throw ValidationException::withMessages([
                'restore_passphrase' => 'Backup passphrase is required.',
            ]);
        }

        return $passphrase;
    }
}
