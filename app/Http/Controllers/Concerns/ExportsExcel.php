<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

trait ExportsExcel
{
    /**
     * @param  class-string  $exportClass  Export class with fromQuery() static method
     */
    protected function downloadExcel(Request $request, $query, string $exportClass, string $filenamePrefix): BinaryFileResponse|RedirectResponse
    {
        $count = (clone $query)->count();

        if ($count === 0) {
            return redirect()->back()->with('error', 'No records found to export for the current filters.');
        }

        $export   = $exportClass::fromQuery($query);
        $filename = $filenamePrefix . '-' . now()->format('Y-m-d') . '.xlsx';

        if ($count > 1000) {
            Excel::queue($export, $filename);

            return redirect()->back()->with(
                'success',
                "Export queued for {$count} records. Ensure the queue worker is running to generate the file."
            );
        }

        return Excel::download($export, $filename);
    }
}
