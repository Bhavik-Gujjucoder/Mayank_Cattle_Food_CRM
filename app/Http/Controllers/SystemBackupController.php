<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateBackupRequest;
use App\Http\Requests\RestoreBackupRequest;
use App\Services\Backup\BackupEnvWriter;
use App\Services\Backup\BackupService;
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
    public function index(BackupService $backupService, BackupEnvWriter $envWriter): View
    {
        $initialized = $envWriter->isInitialized();

        return view('system.backup.index', [
            'page_title' => 'System Backup',
            'initialized' => $initialized,
        ]);
    }

    public function list(Request $request, BackupService $backupService, BackupEnvWriter $envWriter): JsonResponse
    {
        $initialized = $envWriter->isInitialized();

        if ($request->query('for') === 'options') {
            return response()->json([
                'success' => true,
                'initialized' => $initialized,
                'backups' => $initialized ? $this->formatBackups($backupService->listBackups()) : [],
            ]);
        }

        if (! $initialized) {
            return DataTables::of(collect())->make(true);
        }

        $backups = collect($this->formatBackups($backupService->listBackups()));

        return DataTables::of($backups)
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                $filename = data_get($row, 'filename');

                return '<a href="'.e(route('system.backup.download', $filename)).'" class="btn btn-sm btn-outline-primary">'
                    .'<i class="ti ti-download"></i> Download</a>';
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

    public function create(CreateBackupRequest $request, BackupService $backupService): JsonResponse
    {
        try {
            $backupPath = $backupService->create($request->string('create_passphrase')->toString());
        } catch (\Throwable $exception) {
            return $this->errorResponse($exception->getMessage());
        }

        $backups = $this->formatBackups($backupService->listBackups());

        return response()->json([
            'success' => true,
            'message' => 'Backup created successfully: '.basename($backupPath),
            'backup' => $backups[0] ?? null,
            'backups' => $backups,
        ]);
    }

    public function download(string $filename, BackupService $backupService): BinaryFileResponse|JsonResponse
    {
        try {
            $path = $backupService->resolveBackupPath($filename);
        } catch (\Throwable $exception) {
            return $this->errorResponse($exception->getMessage(), 404);
        }

        return response()->download($path, basename($path));
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
                $request->string('restore_passphrase')->toString()
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
            'reload' => true,
        ]);
    }

    /**
     * @param  list<array{filename: string, path: string, size: int, modified_at: string}>  $backups
     * @return list<array{filename: string, path: string, size: int, size_label: string, modified_at: string, download_url: string}>
     */
    private function formatBackups(array $backups): array
    {
        return array_map(function (array $backup) {
            return [
                'filename' => $backup['filename'],
                'path' => $backup['path'],
                'size' => $backup['size'],
                'size_label' => BackupService::formatBytes($backup['size']),
                'modified_at' => $backup['modified_at'],
                'sort_at' => (int) filemtime($backup['path']),
                'download_url' => route('system.backup.download', $backup['filename']),
            ];
        }, $backups);
    }

    private function errorResponse(string $message, int $status = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    private function resolveRestorePath(
        RestoreBackupRequest $request,
        BackupService $backupService,
        ?string &$tempDirectory
    ): string {
        if ($request->input('restore_source') === 'server') {
            return $backupService->resolveBackupPath((string) $request->input('backup_filename'));
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
}
