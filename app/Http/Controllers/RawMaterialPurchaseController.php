<?php

namespace App\Http\Controllers;

use App\Models\RawMaterial;
use App\Models\RawMaterialPurchase;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class RawMaterialPurchaseController extends Controller
{
    /* ------------------------------------------------------------------ */
    /*  Shared validation rules                                            */
    /* ------------------------------------------------------------------ */
    private function rules(): array
    {
        return [
            'raw_material_id' => 'required|exists:raw_materials,id',
            'supplier_id'     => 'required|exists:suppliers,id',
            'invoice_no'      => 'required|string|max:255',
            'invoice_date'    => 'required|date',
            'quantity'        => 'required|numeric|min:0.01',
            'unit_price'      => 'required|numeric|min:0.01',
            'remarks'         => 'nullable|string|max:1000',
        ];
    }

    private function messages(): array
    {
        return [
            'raw_material_id.required' => 'Please select a raw material.',
            'raw_material_id.exists'   => 'Selected raw material is invalid.',
            'supplier_id.required'     => 'Please select a supplier.',
            'supplier_id.exists'       => 'Selected supplier is invalid.',
            'invoice_no.required'      => 'Invoice no is required.',
            'invoice_date.required'    => 'Invoice date is required.',
            'invoice_date.date'        => 'Invoice date must be a valid date.',
            'quantity.required'        => 'Quantity is required.',
            'quantity.numeric'         => 'Quantity must be a number.',
            'quantity.min'             => 'Quantity must be greater than 0.',
            'unit_price.required'      => 'Unit price is required.',
            'unit_price.numeric'       => 'Unit price must be a number.',
            'unit_price.min'           => 'Unit price must be greater than 0.',
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Generate unique purchase ID                                        */
    /* ------------------------------------------------------------------ */
    private function generatePurchaseId(): string
    {
        $year  = date('Y');
        $count = RawMaterialPurchase::withTrashed()->whereYear('created_at', $year)->count();
        return 'MCF/RAW/' . $year . '/' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }

    /* ------------------------------------------------------------------ */
    /*  INDEX                                                              */
    /* ------------------------------------------------------------------ */
    public function index(Request $request)
    {
        $data['page_title'] = 'Purchase Orders';

        if ($request->ajax()) {
            $query = RawMaterialPurchase::with(['raw_material', 'supplier']);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<label class="checkboxs">
                                <input type="checkbox" class="checkbox-item raw_material_purchase_checkbox" data-id="' . $row->id . '">
                                <span class="checkmarks"></span>
                            </label>';
                })
                ->addColumn('raw_material_name', function ($row) {
                    if (! $row->raw_material) return '—';
                    return '<a href="javascript:void(0)" class="open-raw-material-details-modal" data-id="' . $row->raw_material_id . '">
                                <i class="ti ti-eye"></i> ' . e($row->raw_material->name) . '
                            </a>';
                })
                ->addColumn('supplier_name', function ($row) {
                    if (! $row->supplier) return '—';
                    return '<a href="javascript:void(0)" class="open-supplier-details-modal" data-id="' . $row->supplier_id . '">
                                <i class="ti ti-eye"></i> ' . e($row->supplier->name) . '
                            </a>';
                })
                ->editColumn('purchase_unique_id', fn($row) => $row->purchase_unique_id ?? '—')
                ->editColumn('invoice_date',        fn($row) => date('d M Y', strtotime($row->invoice_date)))
                ->editColumn('quantity',             fn($row) => number_format($row->quantity, 2))
                ->editColumn('unit_price',           fn($row) => '₹ ' . number_format($row->unit_price, 2))
                ->editColumn('total_price',          fn($row) => '₹ ' . number_format($row->total_price, 2))
                ->editColumn('paid_amount',          fn($row) => '₹ ' . number_format($row->paid_amount, 2))
                ->editColumn('due_amount',           fn($row) => '₹ ' . number_format($row->due_amount, 2))
                ->editColumn('status',               fn($row) => $row->statusBadge())
                ->addColumn('action', function ($row) {
                    $edit_btn   = '';
                    $delete_btn = '';

                    if (auth()->user()->can('edit-raw-material-purchas-order')) {
                        $edit_btn = '<a href="' . route('raw-material-order.edit', $row->id) . '" class="dropdown-item">
                                        <i class="ti ti-edit text-warning"></i> Edit
                                    </a>';
                    }

                    if (auth()->user()->can('delete-raw-material-purchas-order')) {
                        $delete_btn = '<a href="javascript:void(0)" class="dropdown-item delete-purchase-btn" data-id="' . $row->id . '">
                                        <i class="ti ti-trash text-danger"></i> Delete
                                      </a>
                                      <form action="' . route('raw-material-order.destroy', $row->id) . '" method="POST"
                                          class="delete-form" id="delete-purchase-form-' . $row->id . '">
                                          ' . csrf_field() . method_field('DELETE') . '
                                      </form>';
                    }

                    $payment_btn = '<a href="javascript:void(0);" class="dropdown-item payment-modal" data-id="' . $row->id . '">
                                       <i class="ti ti-coin-rupee text-primary"></i> Payment
                                   </a>';

                    return '<div class="dropdown table-action">
                                <a href="#" class="action-icon" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa fa-ellipsis-v"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">'
                                    . $edit_btn . $delete_btn . $payment_btn .
                                '</div>
                            </div>';
                })
                ->rawColumns(['checkbox', 'raw_material_name', 'supplier_name', 'status', 'action'])
                ->make(true);
        }

        return view('raw_material_purchase.index', $data);
    }

    /* ------------------------------------------------------------------ */
    /*  CREATE                                                             */
    /* ------------------------------------------------------------------ */
    public function create()
    {
        $data['page_title']    = 'Add Purchase Order';
        $data['raw_materials'] = RawMaterial::where('status', 1)->get();
        $data['suppliers']     = Supplier::where('status', 1)->get();
        return view('raw_material_purchase.create', $data);
    }

    /* ------------------------------------------------------------------ */
    /*  STORE                                                              */
    /* ------------------------------------------------------------------ */
    public function store(Request $request)
    {
        $request->validate($this->rules(), $this->messages());

        $quantity    = (float) $request->quantity;
        $unit_price  = (float) $request->unit_price;
        $total_price = $quantity * $unit_price;

        RawMaterialPurchase::create([
            'purchase_unique_id' => $this->generatePurchaseId(),
            'raw_material_id'    => $request->raw_material_id,
            'supplier_id'        => $request->supplier_id,
            'invoice_no'         => $request->invoice_no,
            'invoice_date'       => $request->invoice_date,
            'quantity'           => $quantity,
            'unit_price'         => $unit_price,
            'total_price'        => $total_price,
            'paid_amount'        => 0,
            'due_amount'         => $total_price,
            'status'             => 0,
            'remarks'            => $request->remarks,
            'created_by'         => auth()->id(),
            'updated_by'         => auth()->id(),
        ]);

        return redirect()->route('raw-material-order.index')
                         ->with('success', 'Purchase order created successfully.');
    }

    /* ------------------------------------------------------------------ */
    /*  EDIT                                                               */
    /* ------------------------------------------------------------------ */
    public function edit(RawMaterialPurchase $raw_material_order)
    {
        $data['page_title']            = 'Edit Purchase Order';
        $data['raw_material_purchase'] = $raw_material_order;
        $data['raw_materials']         = RawMaterial::where('status', 1)->get();
        $data['suppliers']             = Supplier::where('status', 1)->get();
        return view('raw_material_purchase.edit', $data);
    }

    /* ------------------------------------------------------------------ */
    /*  UPDATE                                                             */
    /* ------------------------------------------------------------------ */
    public function update(Request $request, RawMaterialPurchase $raw_material_order)
    {
        $request->validate($this->rules(), $this->messages());

        $quantity    = (float) $request->quantity;
        $unit_price  = (float) $request->unit_price;
        $total_price = $quantity * $unit_price;
        $paid_amount = (float) $raw_material_order->paid_amount;
        $due_amount  = max(0, $total_price - $paid_amount);

        $raw_material_order->update([
            'raw_material_id' => $request->raw_material_id,
            'supplier_id'     => $request->supplier_id,
            'invoice_no'      => $request->invoice_no,
            'invoice_date'    => $request->invoice_date,
            'quantity'        => $quantity,
            'unit_price'      => $unit_price,
            'total_price'     => $total_price,
            'due_amount'      => $due_amount,
            'remarks'         => $request->remarks,
            'updated_by'      => auth()->id(),
        ]);

        return redirect()->route('raw-material-order.index')
                         ->with('success', 'Purchase order updated successfully.');
    }

    /* ------------------------------------------------------------------ */
    /*  DESTROY                                                            */
    /* ------------------------------------------------------------------ */
    public function destroy(RawMaterialPurchase $raw_material_order)
    {
        $raw_material_order->delete();

        return redirect()->route('raw-material-order.index')
                         ->with('success', 'Purchase order deleted successfully.');
    }
}
