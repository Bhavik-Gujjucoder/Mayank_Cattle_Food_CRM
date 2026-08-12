<?php

namespace App\Http\Controllers;

use App\Exports\RawMaterialReceivesExport;
use App\Http\Controllers\Concerns\ExportsExcel;
use App\Http\Requests\StoreRawMaterialReceiveRequest;
use App\Http\Requests\UpdateRawMaterialReceiveRequest;
use App\Models\RawMaterial;
use App\Models\RawMaterialOrder;
use App\Models\RawMaterialOrderItem;
use App\Models\RawMaterialReceive;
use App\Models\Supplier;
use App\Models\SupplierBroker;
use App\Services\RawMaterial\RawMaterialFilterService;
use App\Services\RawMaterialCacheService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class RawMaterialReceiveController extends Controller
{
    use ExportsExcel;

    public function index(Request $request)
    {
        $data['page_title']    = 'Raw Material — Received';
        $data['raw_materials'] = RawMaterial::where('status', 1)->orderBy('name')->get();
        $data['orders']        = RawMaterialOrder::with('supplier')->whereIn('status', [0, 1, 2])->orderByDesc('id')->get();

        if ($request->ajax()) {
            $canView   = auth()->user()->can('view-raw-material-receive');
            $canEdit   = auth()->user()->can('edit-raw-material-receive');
            $canDelete = auth()->user()->can('delete-raw-material-receive');

            $query = RawMaterialFilterService::receives($request);

            return DataTables::of($query)
                ->skipAutoFilter()
                ->addIndexColumn()
                ->addColumn('order_unique_id', fn($row) => e($row->order?->order_unique_id ?? '—'))
                ->addColumn('supplier_order_id', fn($row) => e($row->order?->supplier_order_id ?: '—'))
                ->addColumn('category_name', fn($row) => e($row->rawMaterial?->category?->name ?? '—'))
                ->addColumn('material_name', fn($row) => e($row->rawMaterial?->name ?? '—'))
                ->editColumn('freight', fn($row) => RawMaterialCacheService::receiveFreightHtml($row))
                ->editColumn('received_date', fn($row) => $row->received_date?->format('d M Y') ?? '—')
                ->editColumn('status', fn($row) => $row->statusBadge())
                ->addColumn('action', function ($row) use ($canView, $canEdit, $canDelete) {
                    $view   = $canView
                        ? '<a href="' . route('raw-material.receive.show', $row->id) . '" class="dropdown-item"><i class="ti ti-eye text-info"></i> View</a>'
                        : '';
                    $edit   = ($row->isEditable() && $canEdit)
                        ? '<a href="' . route('raw-material.receive.edit', $row->id) . '" class="dropdown-item"><i class="ti ti-edit text-warning"></i> Edit</a>' : '';
                    $mark   = ($row->isEditable() && $canEdit)
                        ? '<a href="javascript:void(0)" class="dropdown-item mark-received-btn" data-url="' . route('raw-material.receive.markReceived', $row->id) . '"><i class="ti ti-check text-success"></i> Mark Received</a>' : '';
                    $cancel = ($row->isEditable() && $canEdit)
                        ? '<a href="javascript:void(0)" class="dropdown-item cancel-receive-btn" data-url="' . route('raw-material.receive.cancel', $row->id) . '"><i class="ti ti-ban text-danger"></i> Cancel</a>' : '';
                    $delete = $canDelete
                        ? '<a href="javascript:void(0)" class="dropdown-item delete-btn" data-id="' . $row->id . '"><i class="ti ti-trash text-danger"></i> Delete</a>
                           <form action="' . route('raw-material.receive.destroy', $row->id) . '" method="POST" class="delete-form" id="delete-form-' . $row->id . '">' . csrf_field() . method_field('DELETE') . '</form>' : '';

                    return '<div class="dropdown table-action"><a href="#" class="action-icon" data-bs-toggle="dropdown"><i class="fa fa-ellipsis-v"></i></a><div class="dropdown-menu dropdown-menu-right">' . $view . $edit . $mark . $cancel . $delete . '</div></div>';
                })
                ->rawColumns(['freight', 'status', 'action'])
                ->make(true);
        }

        return view('raw_material_receive.index', $data);
    }

    public function create()
    {
        $data['page_title'] = 'Add Received Entry';
        $data = array_merge($data, $this->receiveFormLookups());

        return view('raw_material_receive.create', $data);
    }

    public function store(StoreRawMaterialReceiveRequest $request)
    {
        $item = RawMaterialOrderItem::findOrFail($request->raw_material_order_item_id);

        RawMaterialReceive::create([
            'raw_material_id'            => $item->raw_material_id,
            'raw_material_order_id'      => $request->raw_material_order_id,
            'raw_material_order_item_id' => $request->raw_material_order_item_id,
            'qty'                        => $request->qty,
            'freight'                    => $request->freight ?? 0,
            'received_date'              => $request->received_date,
            'status'                     => $request->status,
        ]);

        return redirect()->route('raw-material.receive.index')->with('success', 'Receive entry created successfully.');
    }

    public function show(RawMaterialReceive $raw_material_receive)
    {
        $data['page_title'] = 'View Received Entry';
        $data['receive']    = $raw_material_receive->load(['order.supplier', 'rawMaterial.category', 'orderItem']);

        return view('raw_material_receive.show', $data);
    }

    public function edit(RawMaterialReceive $raw_material_receive)
    {
        if (! $raw_material_receive->isEditable()) {
            return redirect()->route('raw-material.receive.show', $raw_material_receive)
                ->with('error', 'Only on-road entries can be edited.');
        }

        $raw_material_receive->load(['order.supplier', 'order.supplierBroker']);

        $data['page_title'] = 'Edit Received Entry';
        $data['receive']    = $raw_material_receive;
        $data = array_merge($data, $this->receiveFormLookups($raw_material_receive));
        $data['order_items'] = RawMaterialOrderItem::with('rawMaterial')
            ->where('raw_material_order_id', $raw_material_receive->raw_material_order_id)
            ->whereIn('status', [0, 1, 2])
            ->get()
            ->filter(function (RawMaterialOrderItem $item) use ($raw_material_receive) {
                if ((int) $item->id === (int) $raw_material_receive->raw_material_order_item_id) {
                    return true;
                }

                return RawMaterialCacheService::itemHasOrderedRemaining($item);
            })
            ->values();

        return view('raw_material_receive.edit', $data);
    }

    /** @return array{supplier_brokers: \Illuminate\Support\Collection, suppliers: \Illuminate\Support\Collection, receivable_orders: \Illuminate\Support\Collection} */
    protected function receiveFormLookups(?RawMaterialReceive $editingReceive = null): array
    {
        $orders = RawMaterialCacheService::receivableOrders($editingReceive);

        return [
            'supplier_brokers' => SupplierBroker::query()
                ->where('status', 1)
                ->orderBy('name')
                ->get(['id', 'name']),
            'suppliers' => Supplier::query()
                ->where('status', 1)
                ->orderBy('name')
                ->get(['id', 'name', 'supplier_broker_id'])
                ->map(function (Supplier $supplier) {
                    return [
                        'id'                 => (int) $supplier->id,
                        'name'               => $supplier->name,
                        'supplier_broker_id' => (int) $supplier->supplier_broker_id,
                    ];
                })
                ->values(),
            'receivable_orders' => $orders->map(function (RawMaterialOrder $order) {
                $label = $order->order_unique_id;
                if (filled($order->supplier_order_id)) {
                    $label .= ' | ' . $order->supplier_order_id;
                }
                if ($order->supplier) {
                    $label .= ' | ' . $order->supplier->name;
                }

                return [
                    'id'                 => (int) $order->id,
                    'label'              => $label,
                    'supplier_id'        => (int) $order->supplier_id,
                    'supplier_broker_id' => (int) $order->supplier_broker_id,
                ];
            })->values(),
        ];
    }

    public function update(UpdateRawMaterialReceiveRequest $request, RawMaterialReceive $raw_material_receive)
    {
        if (! $raw_material_receive->isEditable()) {
            return redirect()->route('raw-material.receive.index')->with('error', 'Only on-road entries can be edited.');
        }

        $item = RawMaterialOrderItem::findOrFail($request->raw_material_order_item_id);

        $raw_material_receive->update([
            'raw_material_id'            => $item->raw_material_id,
            'raw_material_order_id'      => $request->raw_material_order_id,
            'raw_material_order_item_id' => $request->raw_material_order_item_id,
            'qty'                        => $request->qty,
            'freight'                    => $request->freight ?? 0,
            'received_date'              => $request->received_date,
        ]);

        return redirect()->route('raw-material.receive.index')->with('success', 'Receive entry updated successfully.');
    }

    public function destroy(RawMaterialReceive $raw_material_receive)
    {
        if ((int) $raw_material_receive->status === 1) {
            return redirect()->route('raw-material.receive.index')
                ->with('error', 'Cannot delete — entry is already received.');
        }

        $raw_material_receive->delete();

        return redirect()->route('raw-material.receive.index')->with('success', 'Receive entry deleted successfully.');
    }

    public function markReceived(RawMaterialReceive $raw_material_receive)
    {
        if (! $raw_material_receive->isEditable()) {
            return redirect()->back()->with('error', 'Only on-road entries can be marked as received.');
        }

        $raw_material_receive->update(['status' => 1]);

        return redirect()->back()->with('success', 'Entry marked as received.');
    }

    public function cancel(RawMaterialReceive $raw_material_receive)
    {
        if (! $raw_material_receive->isEditable()) {
            return redirect()->back()->with('error', 'Only on-road entries can be cancelled.');
        }

        $raw_material_receive->update(['status' => 2]);

        return redirect()->back()->with('success', 'Entry cancelled successfully.');
    }

    public function export(Request $request)
    {
        return $this->downloadExcel(
            $request,
            RawMaterialFilterService::receives($request),
            RawMaterialReceivesExport::class,
            'raw-material-receives'
        );
    }

    public function exportListPdf(Request $request)
    {
        $query = RawMaterialFilterService::receives($request);
        $count = (clone $query)->count();

        if ($count === 0) {
            return redirect()->back()->with('error', 'No records found to export for the current filters.');
        }

        $receives = $query->get();
        $filename = 'raw-material-receives-' . now()->format('Y-m-d') . '.pdf';

        $pdf = Pdf::loadView('raw_material_receive.pdf_receives_list', compact('receives'))
            ->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }
}
