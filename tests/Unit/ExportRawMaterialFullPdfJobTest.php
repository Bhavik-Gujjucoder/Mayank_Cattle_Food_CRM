<?php

use App\Jobs\ExportRawMaterialFullPdfJob;

describe('ExportRawMaterialFullPdfJob', function () {
    it('saves pdf file to storage exports directory', function () {
        $filename     = 'test-full-export-' . uniqid() . '.pdf';
        $expectedPath = storage_path('app/exports/' . $filename);

        // Ensure leftover from a previous failed run is cleaned up
        if (file_exists($expectedPath)) {
            unlink($expectedPath);
        }

        $job = new ExportRawMaterialFullPdfJob($filename);
        $job->handle();

        expect(file_exists($expectedPath))->toBeTrue();

        // Cleanup
        @unlink($expectedPath);
    });

    it('creates exports directory when it does not exist', function () {
        $directory = storage_path('app/exports');

        // Temporarily rename directory if it exists, to simulate a missing directory
        $tempDir = $directory . '_test_backup_' . uniqid();
        $existed = is_dir($directory);
        if ($existed) {
            rename($directory, $tempDir);
        }

        $filename     = 'test-dir-creation-' . uniqid() . '.pdf';
        $expectedPath = storage_path('app/exports/' . $filename);

        try {
            $job = new ExportRawMaterialFullPdfJob($filename);
            $job->handle();

            expect(is_dir($directory))->toBeTrue()
                ->and(file_exists($expectedPath))->toBeTrue();
        } finally {
            // Restore original state
            @unlink($expectedPath);
            if ($existed) {
                // Remove the freshly created directory and restore backup
                @rmdir($directory);
                rename($tempDir, $directory);
            }
        }
    });

    it('uses the provided filename for the saved pdf', function () {
        $filename     = 'custom-name-' . uniqid() . '.pdf';
        $expectedPath = storage_path('app/exports/' . $filename);

        try {
            $job = new ExportRawMaterialFullPdfJob($filename);
            $job->handle();

            expect(file_exists($expectedPath))->toBeTrue();
        } finally {
            @unlink($expectedPath);
        }
    });
});
