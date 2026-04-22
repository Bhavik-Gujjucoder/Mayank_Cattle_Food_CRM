<?php

namespace App\Http\Controllers;

use App\Models\RawMaterial;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class RawMaterialController extends Controller
{
    /* ------------------------------------------------------------------ */
    /*  INDEX                                                              */
    /* ------------------------------------------------------------------ */
    public function index(Request $request)
    {
        $data['page_title'] =  'Inventory'; //'Raw Material Management';

        if ($request->ajax()) {
            $query = RawMaterial::query();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<label class="checkboxs">
                                <input type="checkbox" class="checkbox-item raw_material_checkbox" data-id="' . $row->id . '">
                                <span class="checkmarks"></span>
                            </label>';
                })

                ->editColumn('total_stock', fn($row) => number_format($row->total_stock, 2) . ' ' . $row->unit)
                ->editColumn('available_stock', fn($row) => number_format($row->available_stock, 2) . ' ' . $row->unit)
                ->editColumn('used_stock', fn($row) => number_format($row->used_stock, 2) . ' ' . $row->unit)
                ->editColumn('last_purchase_price', fn($row) => '₹ ' . number_format($row->last_purchase_price, 2))
                ->editColumn('average_price', fn($row) => '₹ ' . number_format($row->average_price, 2))
                ->editColumn('status', fn($row) => $row->statusBadge())
                ->addColumn('action', function ($row) {
                    $edit_btn = '<a href="javascript:void(0)" class="dropdown-item edit-raw-material-btn" data-id="' . $row->id . '">
                                    <i class="ti ti-edit text-warning"></i> Edit
                                </a>';

                    $delete_btn = '<a href="javascript:void(0)" class="dropdown-item delete-raw-material-btn" data-id="' . $row->id . '">
                                    <i class="ti ti-trash text-danger"></i> Delete
                                </a>
                                <form action="' . route('raw-material.destroy', $row->id) . '" method="POST"
                                    class="delete-form" id="delete-raw-material-form-' . $row->id . '">
                                    ' . csrf_field() . method_field('DELETE') . '
                                </form>';

                    return '<div class="dropdown table-action">
                                <a href="#" class="action-icon" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa fa-ellipsis-v"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">'
                                    . $edit_btn . $delete_btn .
                                '</div>
                            </div>';
                })
                ->rawColumns(['checkbox', 'total_stock', 'available_stock', 'used_stock',
                              'last_purchase_price', 'average_price', 'status', 'action'])
                ->make(true);
        }

        return view('raw_material.index', $data);
    }

    /* ------------------------------------------------------------------ */
    /*  STORE                                                               */
    /* ------------------------------------------------------------------ */
    public function store(Request $request)
    {
        $request->validate([
            'name'                => 'required|string|max:255|unique:raw_materials,name,NULL,id,deleted_at,NULL',
            'unit'                => 'required|string|max:50',
            'last_purchase_price' => 'nullable|numeric|min:0',
            'status'              => 'required|in:0,1',
        ], [
            'name.required'  => 'Inventory name is required.',
            'name.unique'    => 'This Inventory name already exists.',
            'unit.required'  => 'Unit is required.',
            'status.required'=> 'Status is required.',
        ]);

        RawMaterial::create([
            'name'                => $request->name,
            'unit'                => $request->unit,
            'last_purchase_price' => $request->last_purchase_price ?? 0,
            'status'              => $request->status,
        ]);

        return response()->json(['success' => true, 'message' => 'Raw material created successfully.']);
    }

    /* ------------------------------------------------------------------ */
    /*  EDIT                                                                */
    /* ------------------------------------------------------------------ */
    public function edit(RawMaterial $raw_material)
    {
        return response()->json($raw_material);
    }

    /* ------------------------------------------------------------------ */
    /*  UPDATE                                                              */
    /* ------------------------------------------------------------------ */
    public function update(Request $request, RawMaterial $raw_material)
    {
        $request->validate([
            'name'                => 'required|string|max:255|unique:raw_materials,name,' . $raw_material->id . ',id,deleted_at,NULL',
            'unit'                => 'required|string|max:50',
            'last_purchase_price' => 'nullable|numeric|min:0',
            'status'              => 'required|in:0,1',
        ], [
            'name.required'  => 'Inventory name is required.',
            'name.unique'    => 'This Inventory name already exists.',
            'unit.required'  => 'Unit is required.',
            'status.required'=> 'Status is required.',
        ]);

        $raw_material->update([
            'name'                => $request->name,
            'unit'                => $request->unit,
            'last_purchase_price' => $request->last_purchase_price ?? 0,
            'status'              => $request->status,
        ]);

        return response()->json(['success' => true, 'message' => 'Inventory updated successfully.']);
    }

    /* ------------------------------------------------------------------ */
    /*  DESTROY                                                             */
    /* ------------------------------------------------------------------ */
    public function destroy(RawMaterial $raw_material)
    {
        $raw_material->delete();

        return redirect()->route('raw-material.index')->with('success', 'Inventory deleted successfully.');
    }

    /* ------------------------------------------------------------------ */
    /*  BULK DELETE                                                         */
    /* ------------------------------------------------------------------ */
    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;

        if (!empty($ids)) {
            RawMaterial::whereIn('id', $ids)->delete();
            return response()->json(['message' => 'Selected Inventory deleted successfully.']);
        }

        return response()->json(['message' => 'No records selected.'], 400);
    }
}
