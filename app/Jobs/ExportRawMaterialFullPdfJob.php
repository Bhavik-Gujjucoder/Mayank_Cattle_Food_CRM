<?php

namespace App\Jobs;

use App\Models\RawMaterialOrder;
use App\Support\RawMaterialOrderExportRows;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;

class ExportRawMaterialFullPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $filename
    ) {}

    public function handle(): void
    {
        $orders = RawMaterialOrder::query()
            ->with([
                'supplier',
                'supplierBroker',
                'items.rawMaterial.category',
                'receives',
            ])
            ->orderByDesc('id')
            ->get();

        $built = RawMaterialOrderExportRows::build($orders);

        $directory = storage_path('app/exports');
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        Pdf::loadView('raw_material_order.pdf_report', [
            'title' => 'Raw Material Orders — Full Export',
            'lineHeadings' => RawMaterialOrderExportRows::lineHeadings(),
            'receiveHeadings' => RawMaterialOrderExportRows::receiveHeadings(),
            'groups' => $built['groups'],
        ])->setPaper('a4', 'landscape')->save($directory . DIRECTORY_SEPARATOR . $this->filename);
    }
}
