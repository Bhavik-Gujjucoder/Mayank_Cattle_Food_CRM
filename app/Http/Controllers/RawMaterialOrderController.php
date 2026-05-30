<?php

namespace App\Http\Controllers;

use App\Exports\RawMaterialFullExport;
use App\Exports\RawMaterialOrderSingleExport;
use App\Exports\RawMaterialOrdersExport;
use App\Http\Controllers\Concerns\ExportsExcel;
use App\Http\Requests\StoreRawMaterialOrderRequest;
use App\Http\Requests\UpdateRawMaterialOrderRequest;
use App\Jobs\ExportRawMaterialFullPdfJob;
use App\Models\RawMaterial;
use App\Models\RawMaterialOrder;
use App\Models\RawMaterialOrderItem;
use App\Models\RawMaterialReceive;
use App\Models\Supplier;
use App\Services\RawMaterial\RawMaterialFilterService;
use App\Services\RawMaterialIdGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\DataTables;

class RawMaterialOrderController extends Controller
{
    use ExportsExcel;

    public function index(Request $request)
    {
        $data['page_title'] = 'Raw Material — Orders';
        $data['suppliers']  = Supplier::where('status', 1)->orderBy('name')->get();

        if ($request->ajax()) {
            $query = RawMaterialFilterService::orders($request);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('supplier_name', fn ($row) => e($row->supplier?->name ?? '—'))
                ->editColumn('order_date', fn ($row) => $row->order_date?->format('d M Y') ?? '—')
                ->editColumn('total_qty', fn ($row) => number_format($row->total_qty) . ' tons')
                ->editColumn('total_price', fn ($row) => '₹ ' . number_format($row->total_price, 3))
                ->editColumn('total_freight', fn ($row) => '₹ ' . number_format($row->total_freight, 3))
                ->editColumn('status', fn ($row) => $row->statusBadge())
                ->addColumn('action', function ($row) {
                    $view   = '<a href="' . route('raw-material-order.show', $row->id) . '" class="dropdown-item"><i class="ti ti-eye text-info"></i> View</a>';
                    $edit   = ($row->isEditable() && auth()->user()->can('edit-raw-material-purchas-order'))
                        ? '<a href="' . route('raw-material-order.edit', $row->id) . '" class="dropdown-item"><i class="ti ti-edit text-warning"></i> Edit</a>' : '';
                    $cancel = (in_array((int) $row->status, [0, 1], true) && auth()->user()->can('edit-raw-material-purchas-order'))
                        ? '<a href="javascript:void(0)" class="dropdown-item cancel-order-btn" data-url="' . route('raw-material-order.cancel', $row->id) . '"><i class="ti ti-ban text-danger"></i> Cancel</a>' : '';
                    $exportExcel = auth()->user()->canAny(['add-raw-material-purchas-order', 'edit-raw-material-purchas-order', 'delete-raw-material-purchas-order'])
                        ? '<a href="' . route('raw-material-order.export-order-excel', $row->id) . '" class="dropdown-item"><i class="ti ti-file-spreadsheet text-success"></i> Export Excel</a>' : '';
                    $exportPdf = auth()->user()->canAny(['add-raw-material-purchas-order', 'edit-raw-material-purchas-order', 'delete-raw-material-purchas-order'])
                        ? '<a href="' . route('raw-material-order.export-order-pdf', $row->id) . '" class="dropdown-item"><i class="ti ti-file-type-pdf text-danger"></i> Export PDF</a>' : '';
                    $delete = auth()->user()->can('delete-raw-material-purchas-order')
                        ? '<a href="javascript:void(0)" class="dropdown-item delete-btn" data-id="' . $row->id . '"><i class="ti ti-trash text-danger"></i> Delete</a>
                           <form action="' . route('raw-material-order.destroy', $row->id) . '" method="POST" class="delete-form" id="delete-form-' . $row->id . '">' . csrf_field() . method_field('DELETE') . '</form>' : '';

                    return '<div class="dropdown table-action"><a href="#" class="action-icon" data-bs-toggle="dropdown"><i class="fa fa-ellipsis-v"></i></a><div class="dropdown-menu dropdown-menu-right">' . $view . $edit . $cancel . $exportExcel . $exportPdf . $delete . '</div></div>';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('raw_material_order.index', $data);
    }

    public function create()
    {
        $data['page_title']     = 'Add Raw Material Order';
        $data['suppliers']      = Supplier::where('status', 1)->orderBy('name')->get();
        $data['raw_materials']  = RawMaterial::where('status', 1)->orderBy('name')->get();
        $data['order_unique_id'] = RawMaterialIdGenerator::nextOrderId();

        return view('raw_material_order.create', $data);
    }

    public function store(StoreRawMaterialOrderRequest $request)
    {
        DB::transaction(function () use ($request) {
            $order = RawMaterialOrder::create([
                'order_unique_id' => $request->order_unique_id,
                'supplier_id'     => $request->supplier_id,
                'order_date'      => $request->order_date,
            ]);

            foreach ($request->raw_material_id as $i => $materialId) {
                $order->items()->create([
                    'raw_material_id' => $materialId,
                    'total_qty'       => $request->total_qty[$i],
                    'price'           => $request->price[$i],
                ]);
            }
        });

        return redirect()->route('raw-material-order.index')->with('success', 'Order created successfully.');
    }

    public function show(RawMaterialOrder $raw_material_order)
    {
        $data['page_title'] = 'View Raw Material Order';
        $data['order']      = $raw_material_order->load(['supplier', 'items.rawMaterial', 'receives.rawMaterial']);

        return view('raw_material_order.show', $data);
    }

    public function edit(RawMaterialOrder $raw_material_order)
    {
        if (! $raw_material_order->isEditable()) {
            return redirect()->route('raw-material-order.show', $raw_material_order)
                ->with('error', 'Only pending orders can be edited.');
        }

        $data['page_title']    = 'Edit Raw Material Order';
        $data['order']         = $raw_material_order->load('items');
        $data['suppliers']     = Supplier::where('status', 1)->orderBy('name')->get();
        $data['raw_materials'] = RawMaterial::where('status', 1)->orderBy('name')->get();

        return view('raw_material_order.edit', $data);
    }

    public function update(UpdateRawMaterialOrderRequest $request, RawMaterialOrder $raw_material_order)
    {
        if (! $raw_material_order->isEditable()) {
            return redirect()->route('raw-material-order.index')->with('error', 'Only pending orders can be edited.');
        }

        DB::transaction(function () use ($request, $raw_material_order) {
            $raw_material_order->update([
                'supplier_id' => $request->supplier_id,
                'order_date'  => $request->order_date,
            ]);

            $raw_material_order->items()->forceDelete();

            foreach ($request->raw_material_id as $i => $materialId) {
                $raw_material_order->items()->create([
                    'raw_material_id' => $materialId,
                    'total_qty'       => $request->total_qty[$i],
                    'price'           => $request->price[$i],
                ]);
            }
        });

        return redirect()->route('raw-material-order.index')->with('success', 'Order updated successfully.');
    }

    public function destroy(RawMaterialOrder $raw_material_order)
    {
        if ($raw_material_order->receives()->exists()) {
            return redirect()->route('raw-material-order.index')
                ->with('error', 'Cannot delete — receive entries exist for this order.');
        }

        $raw_material_order->items()->delete();
        $raw_material_order->delete();

        return redirect()->route('raw-material-order.index')->with('success', 'Order deleted successfully.');
    }

    public function cancel(RawMaterialOrder $raw_material_order)
    {
        if (! in_array((int) $raw_material_order->status, [0, 1], true)) {
            return redirect()->back()->with('error', 'This order cannot be cancelled.');
        }

        $raw_material_order->update(['status' => 3]);
        $raw_material_order->items()->update(['status' => 3]);

        return redirect()->back()->with('success', 'Order cancelled successfully.');
    }

    public function orderItems(RawMaterialOrder $raw_material_order)
    {
        $items = $raw_material_order->items()
            ->with('rawMaterial')
            ->whereIn('status', [0, 1])
            ->where('pending_qty', '>', 0)
            ->get()
            ->map(fn ($item) => [
                'id'           => $item->id,
                'label'        => ($item->rawMaterial?->name ?? '—') . ' (Pending: ' . $item->pending_qty . ' tons)',
                'raw_material_id' => $item->raw_material_id,
                'pending_qty'  => $item->pending_qty,
            ]);

        return response()->json($items);
    }

    public function export(Request $request)
    {
        return $this->downloadExcel(
            $request,
            RawMaterialFilterService::orders($request),
            RawMaterialOrdersExport::class,
            'raw-material-orders'
        );
    }

    public function exportListPdf(Request $request)
    {
        $query = RawMaterialFilterService::orders($request);
        $count = (clone $query)->count();

        if ($count === 0) {
            return redirect()->back()->with('error', 'No records found to export for the current filters.');
        }

        $orders   = $query->get();
        $filename = 'raw-material-orders-' . now()->format('Y-m-d') . '.pdf';

        $pdf = Pdf::loadView('raw_material_order.pdf_orders_list', compact('orders'))
            ->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }

    public function exportFull()
    {
        $count    = $this->fullExportRecordCount();
        $filename = 'raw-material-full-export-' . now()->format('Y-m-d') . '.xlsx';
        $export   = new RawMaterialFullExport();

        if ($count > 1000) {
            Excel::queue($export, $filename);

            return redirect()->back()->with(
                'success',
                "Full Excel export queued ({$count} records). Ensure the queue worker is running to generate the file."
            );
        }

        return Excel::download($export, $filename);
    }

    public function exportFullPdf()
    {
        $count    = $this->fullExportRecordCount();
        $filename = 'raw-material-full-export-' . now()->format('Y-m-d') . '.pdf';

        if ($count > 1000) {
            ExportRawMaterialFullPdfJob::dispatch($filename);

            return redirect()->back()->with(
                'success',
                "Full PDF export queued ({$count} records). The file will be saved to storage/app/exports/{$filename}. Ensure the queue worker is running."
            );
        }

        $data = $this->fullExportData();
        $pdf  = Pdf::loadView('raw_material_order.pdf_full_export', $data)
            ->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }

    /** @return array{orders: \Illuminate\Support\Collection, items: \Illuminate\Support\Collection, receives: \Illuminate\Support\Collection} */
    protected function fullExportData(): array
    {
        return [
            'orders'   => RawMaterialOrder::with('supplier')->orderByDesc('id')->get(),
            'items'    => RawMaterialOrderItem::with(['rawMaterial', 'order'])->orderByDesc('id')->get(),
            'receives' => RawMaterialReceive::with(['rawMaterial', 'order'])->orderByDesc('id')->get(),
        ];
    }

    protected function fullExportRecordCount(): int
    {
        return max(
            RawMaterialOrder::count(),
            RawMaterialOrderItem::count(),
            RawMaterialReceive::count()
        );
    }

    public function exportOrderExcel(RawMaterialOrder $raw_material_order)
    {
        $filename = 'order-' . Str::slug($raw_material_order->order_unique_id, '-') . '.xlsx';

        return Excel::download(new RawMaterialOrderSingleExport($raw_material_order), $filename);
    }

    public function exportOrderPdf(RawMaterialOrder $raw_material_order)
    {
        $order = $raw_material_order->load(['supplier', 'items.rawMaterial', 'receives.rawMaterial']);

        $filename = 'order-' . Str::slug($order->order_unique_id, '-') . '.pdf';

        $pdf = Pdf::loadView('raw_material_order.pdf_order_full', compact('order'))
            ->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }

    public function exportPdf(RawMaterialOrder $raw_material_order)
    {
        $order = $raw_material_order->load(['supplier', 'items.rawMaterial']);

        $filename = 'purchase-order-' . Str::slug($order->order_unique_id, '-') . '.pdf';

        $pdf = Pdf::loadView('raw_material_order.pdf', compact('order'))
            ->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }
}
