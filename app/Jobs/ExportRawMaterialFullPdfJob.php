<?php

namespace App\Jobs;

use App\Models\RawMaterialOrder;
use App\Models\RawMaterialOrderItem;
use App\Models\RawMaterialReceive;
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
        $orders = RawMaterialOrder::with('supplier')->orderByDesc('id')->get();
        $items  = RawMaterialOrderItem::with(['rawMaterial', 'order'])->orderByDesc('id')->get();
        $receives = RawMaterialReceive::with(['rawMaterial', 'order'])->orderByDesc('id')->get();

        $directory = storage_path('app/exports');
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        Pdf::loadView('raw_material_order.pdf_full_export', compact('orders', 'items', 'receives'))
            ->setPaper('a4', 'landscape')
            ->save($directory . DIRECTORY_SEPARATOR . $this->filename);
    }
}
