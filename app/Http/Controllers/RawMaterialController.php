<?php

namespace App\Http\Controllers;

use App\Exports\RawMaterialsExport;
use App\Http\Controllers\Concerns\ExportsExcel;
use App\Http\Requests\StoreRawMaterialRequest;
use App\Http\Requests\UpdateRawMaterialRequest;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Services\RawMaterial\RawMaterialFilterService;
use App\Services\RawMaterialIdGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class RawMaterialController extends Controller
{
    use ExportsExcel;

    public function index(Request $request)
    {
        $data['page_title'] = 'Raw Material — Material';

        if ($request->ajax()) {
            $canView   = auth()->user()->can('view-raw-material-inventory');
            $canEdit   = auth()->user()->can('edit-raw-material-inventory');
            $canDelete = auth()->user()->can('delete-raw-material-inventory');

            $query = RawMaterialFilterService::materials($request);

            return DataTables::of($query)
                ->skipAutoFilter()
                ->addIndexColumn()
                ->addColumn('category_name', fn($row) => e($row->category?->name ?? '—'))
                ->editColumn('raw_material_unique_id', fn($row) => e($row->raw_material_unique_id))
                ->editColumn('total_stock', fn($row) => number_format($row->total_stock, 2) . ' ' . $row->unit)
                ->editColumn('available_stock', fn($row) => number_format($row->available_stock, 2) . ' ' . $row->unit)
                ->editColumn('last_purchase_price', fn($row) => '₹ ' . number_format($row->last_purchase_price, 2))
                ->editColumn('average_price', fn($row) => '₹ ' . number_format($row->average_price, 2))
                ->editColumn('status', fn($row) => $row->statusBadge())
                ->addColumn('action', function ($row) use ($canView, $canEdit, $canDelete) {
                    $view = $canView
                        ? '<a href="' . route('raw-material.show', $row->id) . '" class="dropdown-item"><i class="ti ti-eye text-info"></i> View</a>'
                        : '';
                    $edit = $canEdit
                        ? '<a href="' . route('raw-material.edit', $row->id) . '" class="dropdown-item"><i class="ti ti-edit text-warning"></i> Edit</a>'
                        : '';
                    $toggle = $canEdit
                        ? '<a href="javascript:void(0)" class="dropdown-item toggle-status-btn" data-url="' . route('raw-material.toggleStatus', $row->id) . '"><i class="ti ti-toggle-left text-primary"></i> Toggle Status</a>'
                        : '';
                    $delete = $canDelete
                        ? '<a href="javascript:void(0)" class="dropdown-item delete-btn" data-id="' . $row->id . '"><i class="ti ti-trash text-danger"></i> Delete</a>
                           <form action="' . route('raw-material.destroy', $row->id) . '" method="POST" class="delete-form" id="delete-form-' . $row->id . '">' . csrf_field() . method_field('DELETE') . '</form>'
                        : '';

                    return '<div class="dropdown table-action"><a href="#" class="action-icon" data-bs-toggle="dropdown"><i class="fa fa-ellipsis-v"></i></a><div class="dropdown-menu dropdown-menu-right">' . $view . $edit . $toggle . $delete . '</div></div>';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('raw_material.index', $data);
    }

    public function create()
    {
        $data['page_title']             = 'Add Raw Material';
        $data['raw_material_unique_id'] = RawMaterialIdGenerator::nextMaterialId();
        $data['categories']             = RawMaterialCategory::where('status', 1)->orderBy('name')->get();

        return view('raw_material.create', $data);
    }

    public function store(StoreRawMaterialRequest $request)
    {
        RawMaterial::create([
            'raw_material_unique_id'   => RawMaterialIdGenerator::nextMaterialId(),
            'raw_material_category_id' => $request->raw_material_category_id,
            'name'                     => $request->name,
            'unit'                     => $request->unit,
            'status'                   => $request->status,
        ]);

        return redirect()->route('raw-material.index')->with('success', 'Raw material created successfully.');
    }

    public function show(RawMaterial $raw_material)
    {
        $data['page_title']   = 'View Raw Material';
        $data['raw_material'] = $raw_material->load('category');
        $data['order_items']  = $raw_material->orderItems()->with('order.supplier')->latest()->get();

        return view('raw_material.show', $data);
    }

    public function edit(RawMaterial $raw_material)
    {
        $data['page_title']   = 'Edit Raw Material';
        $data['raw_material'] = $raw_material;
        $data['categories']   = RawMaterialCategory::where('status', 1)->orderBy('name')->get();

        return view('raw_material.edit', $data);
    }

    public function update(UpdateRawMaterialRequest $request, RawMaterial $raw_material)
    {
        $raw_material->update($request->only('name', 'raw_material_category_id', 'unit', 'status'));

        return redirect()->route('raw-material.index')->with('success', 'Raw material updated successfully.');
    }

    public function destroy(RawMaterial $raw_material)
    {
        if ($raw_material->orderItems()->exists()) {
            return redirect()->route('raw-material.index')
                ->with('error', 'Cannot delete — this material has purchase order items.');
        }

        $raw_material->delete();

        return redirect()->route('raw-material.index')->with('success', 'Raw material deleted successfully.');
    }

    public function toggleStatus(RawMaterial $raw_material)
    {
        $raw_material->update(['status' => (int) $raw_material->status === 1 ? 0 : 1]);

        return redirect()->back()->with('success', 'Status updated successfully.');
    }

    public function export(Request $request)
    {
        return $this->downloadExcel(
            $request,
            RawMaterialFilterService::materials($request),
            RawMaterialsExport::class,
            'raw-materials'
        );
    }

    public function exportListPdf(Request $request)
    {
        $query = RawMaterialFilterService::materials($request);
        $count = (clone $query)->count();

        if ($count === 0) {
            return redirect()->back()->with('error', 'No records found to export for the current filters.');
        }

        $materials = $query->get();
        $filename  = 'raw-materials-' . now()->format('Y-m-d') . '.pdf';

        $pdf = Pdf::loadView('raw_material.pdf_materials_list', compact('materials'))
            ->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }
}
