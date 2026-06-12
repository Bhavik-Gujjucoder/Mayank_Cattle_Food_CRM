<?php

namespace App\Http\Controllers;

use App\Models\BrandManagement;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class BrandManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view-brand')->only(['index']);
    }

    /* ------------------------------------------------------------------ */
    /*  INDEX                                                             */
    /* ------------------------------------------------------------------ */
    public function index(Request $request)
    {
        $data['page_title'] = 'Brand Management';

        if ($request->ajax()) {
            $canEdit   = auth()->user()->can('edit-brand');
            $canDelete = auth()->user()->can('delete-brand');

            return DataTables::of(BrandManagement::query()->ordered())
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<label class="checkboxs">
                            <input type="checkbox" class="checkbox-item brand_checkbox" data-id="' . $row->id . '">
                            <span class="checkmarks"></span>
                        </label>';
                })
                ->addColumn('action', function ($row) use ($canEdit, $canDelete) {
                    $edit_btn = '<a href="javascript:void(0)" class="dropdown-item edit-btn" data-id="' . $row->id . '">
                        <i class="ti ti-edit text-warning"></i>Edit</a>';

                    $delete_btn = '<a href="javascript:void(0)" class="dropdown-item deleteBrand" data-id="' . $row->id . '">
                        <i class="ti ti-trash text-danger"></i> ' . __('Delete') . '</a>
                        <form action="' . route('brand.destroy', $row->id) . '" method="post" class="delete-form" id="delete-form-' . $row->id . '">'
                        . csrf_field() . method_field('DELETE') . '</form>';

                    $action_btn = '<div class="dropdown table-action">
                        <a href="#" class="action-icon" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa fa-ellipsis-v"></i></a>
                        <div class="dropdown-menu dropdown-menu-right">';

                    $action_btn .= $canEdit ? $edit_btn : '';
                    $action_btn .= $canDelete ? $delete_btn : '';

                    return $action_btn . ' </div></div>';
                })
                ->editColumn('status', fn ($row) => $row->statusBadge())
                ->rawColumns(['action', 'status', 'checkbox'])
                ->make(true);
        }

        return view('brand.index', $data);
    }

    /* ------------------------------------------------------------------ */
    /*  QUICK CREATE FORM (modal from Soda/Order)                         */
    /* ------------------------------------------------------------------ */
    public function quickCreateForm()
    {
        return view('brand.partials.quick-create-form');
    }

    /* ------------------------------------------------------------------ */
    /*  STORE                                                             */
    /* ------------------------------------------------------------------ */
    public function store(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:255|unique:brand_management,name',
            'status' => 'nullable|in:0,1',
        ], [
            'name.required' => 'The brand name field is required.',
            'name.unique'   => 'This brand name already exists.',
        ]);

        $brand = BrandManagement::create([
            'name'   => trim($request->name),
            'status' => $request->input('status', 1),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Brand created successfully.',
            'brand'   => [
                'id'     => $brand->id,
                'name'   => $brand->name,
                'status' => (int) $brand->status,
            ],
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  EDIT                                                              */
    /* ------------------------------------------------------------------ */
    public function edit(BrandManagement $brand)
    {
        return response()->json($brand);
    }

    /* ------------------------------------------------------------------ */
    /*  UPDATE                                                            */
    /* ------------------------------------------------------------------ */
    public function update(Request $request, BrandManagement $brand)
    {
        $request->validate([
            'name'   => 'required|string|max:255|unique:brand_management,name,' . $brand->id,
            'status' => 'required|in:0,1',
        ], [
            'name.required' => 'The brand name field is required.',
            'name.unique'   => 'This brand name already exists.',
        ]);

        $brand->update([
            'name'   => trim($request->name),
            'status' => $request->status,
        ]);

        return response()->json(['success' => true, 'message' => 'Brand updated successfully.']);
    }

    /* ------------------------------------------------------------------ */
    /*  DESTROY                                                           */
    /* ------------------------------------------------------------------ */
    public function destroy(BrandManagement $brand)
    {
        $brand->delete();

        return redirect()->route('brand.index')->with('success', 'Brand deleted successfully.');
    }

    /* ------------------------------------------------------------------ */
    /*  BULK DELETE                                                       */
    /* ------------------------------------------------------------------ */
    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;

        if (! empty($ids)) {
            BrandManagement::whereIn('id', $ids)->delete();

            return response()->json(['message' => 'Selected brands deleted successfully.']);
        }

        return response()->json(['message' => 'No records selected.'], 400);
    }
}
