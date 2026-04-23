<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class ProductController extends Controller
{
    /* ------------------------------------------------------------------ */
    /*  INDEX                                                               */
    /* ------------------------------------------------------------------ */
    public function index(Request $request)
    {
        $data['page_title'] = 'Product Management';
        if ($request->ajax()) {
            $query = Product::query();
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<label class="checkboxs">
                            <input type="checkbox" class="checkbox-item product_checkbox" data-id="' . $row->id . '">
                            <span class="checkmarks"></span>
                        </label>';
                })
                ->addColumn('action', function ($row) {
                    $edit_btn = '<a href="javascript:void(0)" class="dropdown-item edit-btn" data-id="' . $row->id . '">
                        <i class="ti ti-edit text-warning"></i>Edit</a>';

                    $delete_btn = '<a href="javascript:void(0)" class="dropdown-item deleteProduct" data-id="' . $row->id . '">
                        <i class="ti ti-trash text-danger"></i> ' . __('Delete') . '</a>
                        <form action="' . route('product.destroy', $row->id) . '" method="post" class="delete-form" id="delete-form-' . $row->id . '">'
                        . csrf_field() . method_field('DELETE') . '</form>';

                    $action_btn = '<div class="dropdown table-action">
                        <a href="#" class="action-icon" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa fa-ellipsis-v"></i></a>
                        <div class="dropdown-menu dropdown-menu-right">';
                    $action_btn .= auth()->user()->can('edit-product') ? $edit_btn : '';
                    $action_btn .= auth()->user()->can('delete-product') ? $delete_btn : '';
                    return $action_btn . '</div></div>';
                })
                ->editColumn('status', function ($row) {
                    return $row->statusBadge();
                })
                ->rawColumns(['action', 'status', 'checkbox'])
                ->make(true);
        }
        return view('product.index', $data);
    }

    /* ------------------------------------------------------------------ */
    /*  STORE                                                               */
    /* ------------------------------------------------------------------ */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:products,name,NULL,id,deleted_at,NULL',
        ],[
            'name.required' => 'Product name is required',
            'name.unique' => 'Product name already exists',
        ]);
        Product::create([
            'name'   => $request->name,
            'status' => $request->status,
        ]);
        return response()->json(['success' => true, 'message' => 'Product created successfully']);
    }

    /* ------------------------------------------------------------------ */
    /*  EDIT                                                                */
    /* ------------------------------------------------------------------ */
    public function edit(Product $product)
    {
        return response()->json($product);
    }

    /* ------------------------------------------------------------------ */
    /*  UPDATE                                                              */
    /* ------------------------------------------------------------------ */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|unique:products,name,' . $product->id . ',id,deleted_at,NULL',
        ],[
            'name.required' => 'Product name is required',
            'name.unique' => 'Product name already exists',
        ]);
        $product->update([
            'name'   => $request->name,
            'status' => $request->status,
        ]);
        return response()->json(['success' => true, 'message' => 'Product updated successfully']);
    }

    /* ------------------------------------------------------------------ */
    /*  DESTROY                                                             */
    /* ------------------------------------------------------------------ */
    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('product.index')->with('success', 'Product deleted successfully.');
    }

    /* ------------------------------------------------------------------ */
    /*  BULK DELETE                                                         */
    /* ------------------------------------------------------------------ */
    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;
        if (!empty($ids)) {
            Product::whereIn('id', $ids)->delete();
            return response()->json(['message' => 'Selected products deleted successfully!']);
        }
        return response()->json(['message' => 'No records selected!'], 400);
    }
}
