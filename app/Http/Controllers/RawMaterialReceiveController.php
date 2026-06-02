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
use App\Services\RawMaterial\RawMaterialFilterService;
use App\Services\RawMaterialCacheService;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class RawMaterialReceiveController extends Controller
{
    use ExportsExcel;

    public function index(Request $request)
    {
        $data['page_title']    = 'Raw Material — Received';
        $data['raw_materials'] = RawMaterial::where('status', 1)->orderBy('name')->get();
        $data['orders']        = RawMaterialOrder::whereIn('status', [0, 1, 2])->orderByDesc('id')->get();

        if ($request->ajax()) {
            $query = RawMaterialFilterService::receives($request);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('order_unique_id', fn ($row) => e($row->order?->order_unique_id ?? '—'))
                ->addColumn('material_name', fn ($row) => e($row->rawMaterial?->name ?? '—'))
                ->editColumn('freight', function ($row) {
                    $line = RawMaterialCacheService::receiveFreightAmount($row);

                    return '₹ ' . number_format($row->freight, 2) . '/ton<br><small class="text-muted">Line: ₹ ' . number_format($line, 2) . '</small>';
                })
                ->editColumn('received_date', fn ($row) => $row->received_date?->format('d M Y') ?? '—')
                ->editColumn('status', fn ($row) => $row->statusBadge())
                ->addColumn('action', function ($row) {
                    $view   = auth()->user()->can('view-raw-material-receive')
                        ? '<a href="' . route('raw-material.receive.show', $row->id) . '" class="dropdown-item"><i class="ti ti-eye text-info"></i> View</a>'
                        : '';
                    $edit   = ($row->isEditable() && auth()->user()->can('edit-raw-material-receive'))
                        ? '<a href="' . route('raw-material.receive.edit', $row->id) . '" class="dropdown-item"><i class="ti ti-edit text-warning"></i> Edit</a>' : '';
                    $mark   = ($row->isEditable() && auth()->user()->can('edit-raw-material-receive'))
                        ? '<a href="javascript:void(0)" class="dropdown-item mark-received-btn" data-url="' . route('raw-material.receive.markReceived', $row->id) . '"><i class="ti ti-check text-success"></i> Mark Received</a>' : '';
                    $cancel = ($row->isEditable() && auth()->user()->can('edit-raw-material-receive'))
                        ? '<a href="javascript:void(0)" class="dropdown-item cancel-receive-btn" data-url="' . route('raw-material.receive.cancel', $row->id) . '"><i class="ti ti-ban text-danger"></i> Cancel</a>' : '';
                    $delete = auth()->user()->can('delete-raw-material-receive')
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
        $data['orders']     = RawMaterialOrder::whereIn('status', [0, 1])
            ->orderByDesc('id')->get();

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
        $data['receive']    = $raw_material_receive->load(['order.supplier', 'rawMaterial', 'orderItem']);

        return view('raw_material_receive.show', $data);
    }

    public function edit(RawMaterialReceive $raw_material_receive)
    {
        if (! $raw_material_receive->isEditable()) {
            return redirect()->route('raw-material.receive.show', $raw_material_receive)
                ->with('error', 'Only on-road entries can be edited.');
        }

        $data['page_title'] = 'Edit Received Entry';
        $data['receive']    = $raw_material_receive;
        $data['orders']     = RawMaterialOrder::whereIn('status', [0, 1])->orderByDesc('id')->get();
        $data['order_items'] = RawMaterialOrderItem::with('rawMaterial')
            ->where('raw_material_order_id', $raw_material_receive->raw_material_order_id)
            ->whereIn('status', [0, 1])
            ->get();

        return view('raw_material_receive.edit', $data);
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
}
