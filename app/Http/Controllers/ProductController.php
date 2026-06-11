<?php

namespace App\Http\Controllers;

use App\Models\BrandManagement;
use App\Models\Product;
use App\Support\ProductUnit;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class ProductController extends Controller
{
    /** Fixed unit options */
    private const UNITS = ProductUnit::UNITS;

    /* ------------------------------------------------------------------ */
    /*  INDEX                                                               */
    /* ------------------------------------------------------------------ */
    public function index(Request $request)
    {
        $data['page_title'] = 'Product Management';
        $data['brands']     = BrandManagement::activeForDropdown();

        if ($request->ajax()) {
            $query = Product::with('brand');
            if ($request->has('brand_id') && $request->brand_id !== 'all') {
                $query->where('brand_id', $request->brand_id);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<label class="checkboxs">
                                <input type="checkbox" class="checkbox-item product_checkbox" data-id="' . $row->id . '">
                                <span class="checkmarks"></span>
                            </label>';
                })
                ->addColumn('brand_name', function ($row) {
                    return $row->brand?->name ?? '—';
                })
                // ->editColumn('price', fn($row) => '₹ ' . number_format($row->price, 2))
                ->editColumn('status', fn($row) => $row->statusBadge())
                ->addColumn('action', function ($row) {
                    $edit_btn = '<a href="javascript:void(0)" class="dropdown-item edit-btn" data-id="' . $row->id . '">
                                    <i class="ti ti-edit text-warning"></i> Edit
                                </a>';

                    $delete_btn = '<a href="javascript:void(0)" class="dropdown-item deleteProduct" data-id="' . $row->id . '">
                                    <i class="ti ti-trash text-danger"></i> Delete
                                </a>
                                <form action="' . route('product.destroy', $row->id) . '" method="POST"
                                    class="delete-form" id="delete-form-' . $row->id . '">'
                        . csrf_field() . method_field('DELETE') .
                        '</form>';

                    $action_btn  = '<div class="dropdown table-action">
                                        <a href="#" class="action-icon" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fa fa-ellipsis-v"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right">';
                    $action_btn .= auth()->user()->can('edit-product')   ? $edit_btn   : '';
                    $action_btn .= auth()->user()->can('delete-product')  ? $delete_btn : '';
                    $action_btn .= '</div></div>';

                    return $action_btn;
                })
                ->rawColumns(['checkbox', 'status', 'action'])
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
            'name'     => 'required|string|max:255|unique:products,name,NULL,id,deleted_at,NULL',
            'brand_id' => 'required|exists:brand_management,id',
            'unit'     => 'required|in:' . implode(',', self::UNITS),
            'price'    => 'nullable|numeric|min:0',
            'status'   => 'required|in:0,1',
        ], [
            'name.required'     => 'Product name is required.',
            'name.unique'       => 'Product name already exists.',
            'brand_id.required' => 'Please select a brand.',
            'brand_id.exists'   => 'Selected brand is invalid.',
            'unit.required'     => 'Please select a unit.',
            'unit.in'           => 'Unit must be ' . implode(', ', self::UNITS) . '.',
            // 'price.nullable'    => 'Price is optional.',
            'price.numeric'     => 'Price must be a number.',
            'price.min'         => 'Price cannot be negative.',
            'status.required'   => 'Status is required.',
        ]);

        Product::create([
            'name'     => $request->name,
            'brand_id' => $request->brand_id,
            'unit'     => $request->unit,
            'price'    => $request->price ?? 0,
            'status'   => $request->status,
        ]);

        return response()->json(['success' => true, 'message' => 'Product created successfully.']);
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
            'name'     => 'required|string|max:255|unique:products,name,' . $product->id . ',id,deleted_at,NULL',
            'brand_id' => 'required|exists:brand_management,id',
            'unit'     => 'required|in:' . implode(',', self::UNITS),
            'price'    => 'nullable|numeric|min:0',
            'status'   => 'required|in:0,1',
        ], [
            'name.required'     => 'Product name is required.',
            'name.unique'       => 'Product name already exists.',
            'brand_id.required' => 'Please select a brand.',
            'brand_id.exists'   => 'Selected brand is invalid.',
            'unit.required'     => 'Please select a unit.',
            'unit.in'           => 'Unit must be ' . implode(', ', self::UNITS) . '.',
            // 'price.required'    => 'Price is required.',
            'price.numeric'     => 'Price must be a number.',
            'price.min'         => 'Price cannot be negative.',
            'status.required'   => 'Status is required.',
        ]);

        $product->update([
            'name'     => $request->name,
            'brand_id' => $request->brand_id,
            'unit'     => $request->unit,
            'price'    => $request->price ?? 0,
            'status'   => $request->status,
        ]);

        return response()->json(['success' => true, 'message' => 'Product updated successfully.']);
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
        if (! empty($ids)) {
            Product::whereIn('id', $ids)->delete();
            return response()->json(['message' => 'Selected products deleted successfully.']);
        }
        return response()->json(['message' => 'No records selected.'], 400);
    }
}
