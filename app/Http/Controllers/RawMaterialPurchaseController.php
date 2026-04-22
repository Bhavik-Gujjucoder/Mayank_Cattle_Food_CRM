<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

use App\Models\RawMaterialPurchase;
use App\Models\RawMaterial;
use App\Models\Supplier;
class RawMaterialPurchaseController extends Controller
{
    /* ------------------------------------------------------------------ */
    /*  Shared validation rules                                           */
    /* ------------------------------------------------------------------ */
    private function rules(int $ignoreId = 0): array
    {
        return [
            'raw_material_id'   => 'required|exists:raw_materials,id',
            'supplier_id'       => 'required|exists:suppliers,id',
            'invoice_no'        => 'required|string|max:255',
            'invoice_date'      => 'required|date',
            'quantity'          => 'required|numeric|min:1',
            'unit_price'        => 'required|numeric|min:1',
            'total_price'       => 'required|numeric|min:1',
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  VALIDATION MESSAGES                                               */
    /* ------------------------------------------------------------------ */
    private function messages(): array
    {
        return [
            'raw_material_id.required'   => 'Please select a raw material.',
            'raw_material_id.exists'     => 'Selected raw material is invalid.',
            'supplier_id.required'       => 'Please select a supplier.',
            'supplier_id.exists'         => 'Selected supplier is invalid.',
            'invoice_no.required'        => 'Invoice no is required.',
            'invoice_date.required'      => 'Invoice date is required.',
            'quantity.required'          => 'Quantity is required.',
            'quantity.numeric'           => 'Quantity must be a number.',
            'quantity.min'               => 'Quantity must be greater than 0.',
            'unit_price.required'        => 'Unit price is required.',
            'unit_price.numeric'         => 'Unit price must be a number.',
            'unit_price.min'             => 'Unit price must be greater than 0.',
            'total_price.required'       => 'Total price is required.',
            'total_price.numeric'        => 'Total price must be a number.',
            'total_price.min'            => 'Total price must be greater than 0.',
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Index Page                                                       */
    /* ------------------------------------------------------------------ */
    public function index(Request $request)
    {
        $data['page_title'] = 'Purchase Order';
        if($request->ajax()){
            $data = RawMaterialPurchase::with(['raw_material','supplier'])->query();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<label class="checkboxs">
                            <input type="checkbox" class="checkbox-item city_checkbox" data-id="' . $row->id . '">
                            <span class="checkmarks"></span>
                        </label>';
                })
                ->editColumn('raw_material_name', function ($row) {
                    return $row->raw_material->name;
                })
                ->editColumn('supplier_name', function ($row) {
                    return $row->supplier->name;
                })
                ->editColumn('invoice_no', function ($row) {
                    return $row->invoice_no;
                })
                ->editColumn('invoice_date', function ($row) {
                    return date('d M Y', strtotime($row->invoice_date));
                })
                ->editColumn('quantity', function ($row) {
                    return number_format($row->quantity, 2);
                })
                ->editColumn('unit_price', function ($row) {
                    return number_format($row->unit_price, 2);
                })
                ->editColumn('total_price', function ($row) {
                    return number_format($row->total_price, 2);
                })
                ->editColumn('status', function ($row) {
                    return $row->statusBadge();
                })
                ->editColumn('paid_amount', function ($row) {
                    return number_format($row->paid_amount, 2);
                })
                ->editColumn('due_amount', function ($row) {
                    return number_format($row->due_amount, 2);
                })
                ->addColumn('action', function ($row) {
                    $edit_btn = '<a href="javascript:void(0)" class="dropdown-item edit-btn"  data-id="' . $row->id . '"
                    class="btn btn-outline-warning btn-sm edit-btn"><i class="ti ti-edit text-warning"></i>Edit</a>';

                    $delete_btn = '<a href="javascript:void(0)" class="dropdown-item deleteCity"  data-id="' . $row->id . '"
                    class="btn btn-outline-warning btn-sm edit-btn"> <i class="ti ti-trash text-danger"></i> ' . __('Delete') . '</a><form action="' . route('city.destroy', $row->id) . '" method="post" class="delete-form" id="delete-form-' . $row->id . '" >'
                        . csrf_field() . method_field('DELETE') . '</form>';

                    $action_btn = '<div class="dropdown table-action">
                                             <a href="#" class="action-icon" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa fa-ellipsis-v"></i></a>
                                             <div class="dropdown-menu dropdown-menu-right">';
                    // Auth::user()->can('manage users') ? $action_btn .= $edit_btn : '';
                    // Auth::user()->can('manage users') ? $action_btn .= $delete_btn : '';
                    $action_btn .= $edit_btn;
                    $action_btn .= $delete_btn;

                    return $action_btn . ' </div></div>';
                })
                ->rawColumns(['status'])
                ->make(true);
        }
        return view('raw_material_purchase.index', $data);
    }

    /* ------------------------------------------------------------------ */
    /*  Create Page                                                      */
    /* ------------------------------------------------------------------ */
    public function create()
    {
        $data['page_title'] = 'Add - Purchase Raw Material';
        $data['raw_materials'] = RawMaterial::where('status', 1)->get();
        $data['suppliers'] = Supplier::where('status', 1)->get();
        return view('raw_material_purchase.create', $data);
    }

    /* ------------------------------------------------------------------ */
    /*  Store Data                                                       */
    /* ------------------------------------------------------------------ */
    public function store(Request $request)
    {
        $request->validate($this->rules(), $this->messages());
        $data = $request->all();
        $data['created_by'] = auth()->user()->id;
        $data['updated_by'] = auth()->user()->id;
    }

    /* ------------------------------------------------------------------ */
    /*  Edit Page                                                      */
    /* ------------------------------------------------------------------ */
    public function edit(RawMaterialPurchase $raw_material_purchase)
    {
        $data['page_title'] = 'Edit - Purchase Raw Material';
        $data['raw_material_purchase'] = $raw_material_purchase;
        $data['raw_materials'] = RawMaterial::where('status', 1)->get();
        $data['suppliers'] = Supplier::where('status', 1)->get();
        return view('raw_material_purchase.edit', $data);
    }
}
