<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class SupplierController extends Controller
{
    /* ------------------------------------------------------------------ */
    /*  INDEX                                                               */
    /* ------------------------------------------------------------------ */
    public function index(Request $request)
    {
        $data['page_title'] = 'Supplier Management';

        if ($request->ajax()) {
            $query = Supplier::query();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<label class="checkboxs">
                                <input type="checkbox" class="checkbox-item supplier_checkbox" data-id="' . $row->id . '">
                                <span class="checkmarks"></span>
                            </label>';
                })
                ->editColumn('mobile', fn($row) => $row->mobile ?? '-')
                ->editColumn('email', fn($row) => $row->email ?? '-')
                ->editColumn('address', function ($row) {
                    return $row->address
                        ? '<span title="' . e($row->address) . '">' . e(\Str::limit($row->address, 40)) . '</span>'
                        : '-';
                })
                ->editColumn('opening_balance', fn($row) => '₹ ' . number_format($row->opening_balance, 2))
                ->editColumn('status', fn($row) => $row->statusBadge())
                ->addColumn('action', function ($row) {
                    $edit_btn = '<a href="javascript:void(0)" class="dropdown-item edit-supplier-btn" data-id="' . $row->id . '">
                                    <i class="ti ti-edit text-warning"></i> Edit
                                </a>';

                    $delete_btn = '<a href="javascript:void(0)" class="dropdown-item delete-supplier-btn" data-id="' . $row->id . '">
                                    <i class="ti ti-trash text-danger"></i> Delete
                                </a>
                                <form action="' . route('supplier.destroy', $row->id) . '" method="POST"
                                    class="delete-form" id="delete-supplier-form-' . $row->id . '">
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
                ->rawColumns(['checkbox', 'address', 'opening_balance', 'status', 'action'])
                ->make(true);
        }

        return view('supplier.index', $data);
    }

    /* ------------------------------------------------------------------ */
    /*  STORE                                                               */
    /* ------------------------------------------------------------------ */
    public function store(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'mobile'          => 'required|string|max:20',
            'email'           => 'required|email|unique:suppliers,email,NULL,id,deleted_at,NULL',
            'address'         => 'required|string',
            'opening_balance' => 'nullable|numeric|min:0',
            'status'          => 'required|in:0,1',
        ], [
            'name.required'  => 'Supplier name is required.',
            'email.email'    => 'Please enter a valid email address.',
            'status.required'=> 'Status is required.',
        ]);

        Supplier::create([
            'name'            => $request->name,
            'mobile'          => $request->mobile ?: null,
            'email'           => $request->email ?: null,
            'address'         => $request->address ?: null,
            'opening_balance' => $request->opening_balance ?? 0,
            'status'          => $request->status,
        ]);

        return response()->json(['success' => true, 'message' => 'Supplier created successfully.']);
    }

    /* ------------------------------------------------------------------ */
    /*  EDIT                                                                */
    /* ------------------------------------------------------------------ */
    public function edit(Supplier $supplier)
    {
        return response()->json($supplier);
    }

    /* ------------------------------------------------------------------ */
    /*  UPDATE                                                              */
    /* ------------------------------------------------------------------ */
    public function update(Request $request, Supplier $supplier)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'mobile'          => 'required|string|max:20',
            'email'           => 'required|email|unique:suppliers,email,' . $supplier->id . ',id,deleted_at,NULL',
            'address'         => 'required|string',
            'opening_balance' => 'nullable|numeric|min:0',
            'status'          => 'required|in:0,1',
        ], [
            'name.required'  => 'Supplier name is required.',
            'mobile.required'=> 'Mobile number is required.',
            'email.required' => 'Email address is required.',
            'email.email'    => 'Please enter a valid email address.',
            'email.unique'   => 'Email address already exists.',
            'address.required'=> 'Address is required.',
            'status.required'=> 'Status is required.',
        ]);

        $supplier->update([
            'name'            => $request->name,
            'mobile'          => $request->mobile ?: null,
            'email'           => $request->email ?: null,
            'address'         => $request->address ?: null,
            'opening_balance' => $request->opening_balance ?? 0,
            'status'          => $request->status,
        ]);

        return response()->json(['success' => true, 'message' => 'Supplier updated successfully.']);
    }

    /* ------------------------------------------------------------------ */
    /*  DESTROY                                                             */
    /* ------------------------------------------------------------------ */
    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return redirect()->route('supplier.index')->with('success', 'Supplier deleted successfully.');
    }

    /* ------------------------------------------------------------------ */
    /*  BULK DELETE                                                         */
    /* ------------------------------------------------------------------ */
    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;

        if (!empty($ids)) {
            Supplier::whereIn('id', $ids)->delete();
            return response()->json(['message' => 'Selected suppliers deleted successfully.']);
        }

        return response()->json(['message' => 'No records selected.'], 400);
    }
}
