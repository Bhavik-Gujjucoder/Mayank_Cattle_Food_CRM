<?php

namespace App\Http\Controllers;

use App\Exports\RawMaterialCategoriesExport;
use App\Http\Controllers\Concerns\ExportsExcel;
use App\Http\Requests\StoreRawMaterialCategoryRequest;
use App\Http\Requests\UpdateRawMaterialCategoryRequest;
use App\Models\RawMaterialCategory;
use App\Services\RawMaterial\RawMaterialFilterService;
use App\Services\RawMaterialIdGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class RawMaterialCategoryController extends Controller
{
    use ExportsExcel;

    public function index(Request $request)
    {
        $data['page_title'] = 'Raw Material — Category';

        if ($request->ajax()) {
            $canView   = auth()->user()->can('view-raw-material-category');
            $canEdit   = auth()->user()->can('edit-raw-material-category');
            $canDelete = auth()->user()->can('delete-raw-material-category');

            $query = RawMaterialFilterService::categories($request);

            return DataTables::of($query)
                ->skipAutoFilter()
                ->addIndexColumn()
                ->editColumn('category_unique_id', fn($row) => e($row->category_unique_id))
                ->editColumn('status', fn($row) => $row->statusBadge())
                ->addColumn('action', function ($row) use ($canView, $canEdit, $canDelete) {
                    $view = $canView
                        ? '<a href="' . route('raw-material.category.show', $row->id) . '" class="dropdown-item"><i class="ti ti-eye text-info"></i> View</a>'
                        : '';
                    $edit = $canEdit
                        ? '<a href="' . route('raw-material.category.edit', $row->id) . '" class="dropdown-item"><i class="ti ti-edit text-warning"></i> Edit</a>'
                        : '';
                    $toggle = $canEdit
                        ? '<a href="javascript:void(0)" class="dropdown-item toggle-status-btn" data-url="' . route('raw-material.category.toggleStatus', $row->id) . '"><i class="ti ti-toggle-left text-primary"></i> Toggle Status</a>'
                        : '';
                    $delete = $canDelete
                        ? '<a href="javascript:void(0)" class="dropdown-item delete-btn" data-id="' . $row->id . '"><i class="ti ti-trash text-danger"></i> Delete</a>
                           <form action="' . route('raw-material.category.destroy', $row->id) . '" method="POST" class="delete-form" id="delete-form-' . $row->id . '">' . csrf_field() . method_field('DELETE') . '</form>'
                        : '';

                    return '<div class="dropdown table-action"><a href="#" class="action-icon" data-bs-toggle="dropdown"><i class="fa fa-ellipsis-v"></i></a><div class="dropdown-menu dropdown-menu-right">' . $view . $edit . $toggle . $delete . '</div></div>';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('raw_material_category.index', $data);
    }

    public function create()
    {
        $data['page_title']             = 'Add Raw Material Category';
        $data['category_unique_id']     = RawMaterialIdGenerator::nextCategoryId();

        return view('raw_material_category.create', $data);
    }

    public function store(StoreRawMaterialCategoryRequest $request)
    {
        RawMaterialCategory::create([
            'category_unique_id' => RawMaterialIdGenerator::nextCategoryId(),
            'name'               => trim($request->name),
            'status'             => $request->status,
        ]);

        return redirect()->route('raw-material.category.index')->with('success', 'Category created successfully.');
    }

    public function show(RawMaterialCategory $raw_material_category)
    {
        $data['page_title'] = 'View Raw Material Category';
        $data['category']   = $raw_material_category;
        $data['materials']  = $raw_material_category->materials()->latest()->get();

        return view('raw_material_category.show', $data);
    }

    public function edit(RawMaterialCategory $raw_material_category)
    {
        $data['page_title'] = 'Edit Raw Material Category';
        $data['category']   = $raw_material_category;

        return view('raw_material_category.edit', $data);
    }

    public function update(UpdateRawMaterialCategoryRequest $request, RawMaterialCategory $raw_material_category)
    {
        $raw_material_category->update([
            'name'   => trim($request->name),
            'status' => $request->status,
        ]);

        return redirect()->route('raw-material.category.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(RawMaterialCategory $raw_material_category)
    {
        if ($raw_material_category->materials()->exists()) {
            return redirect()->route('raw-material.category.index')
                ->with('error', 'Cannot delete — materials exist under this category.');
        }

        $raw_material_category->delete();

        return redirect()->route('raw-material.category.index')->with('success', 'Category deleted successfully.');
    }

    public function toggleStatus(RawMaterialCategory $raw_material_category)
    {
        $raw_material_category->update([
            'status' => (int) $raw_material_category->status === 1 ? 0 : 1,
        ]);

        return redirect()->back()->with('success', 'Status updated successfully.');
    }

    public function export(Request $request)
    {
        return $this->downloadExcel(
            $request,
            RawMaterialFilterService::categories($request),
            RawMaterialCategoriesExport::class,
            'raw-material-categories'
        );
    }

    public function exportListPdf(Request $request)
    {
        $query = RawMaterialFilterService::categories($request);
        $count = (clone $query)->count();

        if ($count === 0) {
            return redirect()->back()->with('error', 'No records found to export for the current filters.');
        }

        $categories = $query->get();
        $filename   = 'raw-material-categories-' . now()->format('Y-m-d') . '.pdf';

        $pdf = Pdf::loadView('raw_material_category.pdf_categories_list', compact('categories'))
            ->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }
}
