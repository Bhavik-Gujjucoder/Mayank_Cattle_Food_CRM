<?php

namespace App\Http\Controllers;

use App\Models\BrandManagement;
use App\Models\DealerManagement;
use App\Models\OrderManagement;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class OrderManagementController extends Controller
{
    /* ------------------------------------------------------------------ */
    /*  INDEX                                                               */
    /* ------------------------------------------------------------------ */
    public function index(Request $request)
    {
        $data['page_title'] = 'Soda/Order Management';
        $data['brokers']    = User::whereHas('roles', fn($q) => $q->where('name', 'broker'))->get();
        $data['brands']     = BrandManagement::where('status', 1)->orderBy('name')->get();

        if ($request->ajax()) {
            $query = OrderManagement::with(['broker', 'brand', 'dealer']);

            if (auth()->user()->hasRole('broker')) {
                $query->where('broker_id', auth()->id());
            }

             if ($request->has('brand_id') && $request->brand_id !== 'all') {
                $query->where('brand_id', $request->brand_id);
            }

            // Broker filter (only if user is not broker)
            if (!auth()->user()->hasRole('broker') && $request->has('broker_id') && $request->broker_id !== 'all') {
                $query->where('broker_id', $request->broker_id);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<label class="checkboxs">
                                <input type="checkbox" class="checkbox-item order_checkbox" data-id="' . $row->id . '">
                                <span class="checkmarks"></span>
                            </label>';
                })
                ->addColumn('broker_name', fn($row) => $row->broker?->name ?? '—')
                ->addColumn('brand_name',  fn($row) => $row->brand?->name  ?? '—')
                ->addColumn('dealer_name', fn($row) => $row->dealer?->user?->name ?? $row->dealer?->firm_shop_name ?? '—')
                ->editColumn('order_date',  fn($row) => $row->order_date?->format('d M Y') ?? '—')
                ->editColumn('grand_total', fn($row) => '₹ ' . number_format($row->grand_total, 2))
                ->addColumn('payment_status', fn($row) => $row->paymentBadge())
                // ->addColumn('order_status', fn($row) => '<span class="badge badge-pill badge-status bg-secondary">Pending</span>')
                ->addColumn('action', function ($row) {
                    // $dispatch_btn = '<a href="' . route('dispatch.orderHistory', $row->id) . '" class="dropdown-item">
                    //                     <i class="ti ti-truck me-1 text-info"></i> Dispatch
                    //                 </a>';
                    $edit_btn = '<a href="' . route('order.edit', $row->id) . '" class="dropdown-item">
                                    <i class="ti ti-edit text-warning"></i> Edit
                                </a>';
                    $delete_btn = '<a href="javascript:void(0)" class="dropdown-item deleteOrder" data-id="' . $row->id . '">
                                    <i class="ti ti-trash text-danger"></i> Delete
                                </a>
                                <form action="' . route('order.destroy', $row->id) . '" method="POST"
                                    class="delete-form" id="order-delete-form-' . $row->id . '">'
                                    . csrf_field() . method_field('DELETE') .
                                '</form>';
                    $btn  = '<div class="dropdown table-action">
                                 <a href="#" class="action-icon" data-bs-toggle="dropdown" aria-expanded="false">
                                     <i class="fa fa-ellipsis-v"></i>
                                 </a>
                                 <div class="dropdown-menu dropdown-menu-right">';
                    // $btn .= $dispatch_btn;
                    $btn .= auth()->user()->can('edit-order')   ? $edit_btn   : '';
                    $btn .= auth()->user()->can('delete-order') ? $delete_btn : '';
                    $btn .= '</div></div>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'payment_status', 'order_status', 'action'])
                ->make(true);
        }
        return view('order_management.index', $data);
    }

    /* ------------------------------------------------------------------ */
    /*  CREATE                                                              */
    /* ------------------------------------------------------------------ */
    public function create()
    {
        $data['page_title'] = 'Add - Soda/Order';
        $data['brands']     = BrandManagement::where('status', 1)->orderBy('name')->get();
        $data['brokers']    = User::whereHas('roles', fn($q) => $q->where('name', 'broker'))->get();
        $data['products']   = Product::where('status', 1)->orderBy('name')->get();

        /* Auto-generate financial-year Order ID */
        $now           = now();
        $startYear     = $now->month >= 4 ? $now->year : $now->year - 1;
        $financialYear = $startYear . '-' . substr($startYear + 1, -2);
        $nextSeq       = OrderManagement::withTrashed()->count() + 1;
        $data['order_id'] = 'ORD/' . $financialYear . '/' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

        return view('order_management.create', $data);
    }

    /* ------------------------------------------------------------------ */
    /*  STORE                                                               */
    /* ------------------------------------------------------------------ */
    public function store(Request $request)
    {
        $validated = $this->validateOrder($request);

        /* Calculate totals from submitted line items */
        $totalAmount = 0;
        $items       = [];
        foreach ($validated['product_id'] as $i => $productId) {
            $qty   = (int)   $validated['qty'][$i];
            $price = (float) $validated['price'][$i];
            $total = $qty * $price;
            $totalAmount += $total;
            $items[] = [
                'product_id'  => $productId,
                'qty'         => $qty,
                'unit_price'  => $price,
                'total_price' => $total,
            ];
        }

        $order = OrderManagement::create([
            'unique_order_id'     => $validated['unique_order_id'],
            'broker_id'           => $validated['broker_id'],
            'brand_id'            => $validated['brand_id'],
            'dealer_id'           => $validated['dealer_id'],
            'order_date'          => $validated['order_date'],
            'delivery_address'    => $validated['delivery_address'],
            'payment_status'      => $validated['payment_status'],
            'partial_paid_amount' => $validated['payment_status'] === 'partial'
                                     ? $validated['partial_paid_amount'] : null,
            'total_order_amount'  => $totalAmount,
            'grand_total'         => $totalAmount,
            'status'              => 1,
        ]);

        $order->items()->createMany($items);

        return redirect()->route('order.index')
                         ->with('success', 'Order created successfully.');
    }

    /* ------------------------------------------------------------------ */
    /*  EDIT                                                                */
    /* ------------------------------------------------------------------ */
    public function edit(OrderManagement $order)
    {
        $data['page_title'] = 'Edit - Soda/Order';
        $data['order']      = $order->load('items.product');
        $data['brands']     = BrandManagement::where('status', 1)->orderBy('name')->get();
        $data['brokers']    = User::whereHas('roles', fn($q) => $q->where('name', 'broker'))->get();
        $data['products']   = Product::where('status', 1)->orderBy('name')->get();

        /* Pre-load dealers for the order's broker + brand so the dropdown
           is already populated when the edit page opens.                  */
        $data['dealers'] = DealerManagement::with('user')
            ->where('broker_id', $order->broker_id)
            ->where('brand_id',  $order->brand_id)
            ->get();

        return view('order_management.edit', $data);
    }

    /* ------------------------------------------------------------------ */
    /*  UPDATE                                                              */
    /* ------------------------------------------------------------------ */
    public function update(Request $request, OrderManagement $order)
    {
        $validated = $this->validateOrder($request, $order->id);

        /* IDs that currently exist in the DB for this order */
        $existingIds = $order->items()->pluck('id')->toArray();

        /* IDs submitted from the form (empty string = new row, skip those) */
        $submittedItemIds = collect($request->input('item_id', []))
            ->filter(fn($id) => $id !== '' && $id !== null)
            ->map(fn($id)    => (int) $id)
            ->toArray();

        /* Any existing ID not present in the submission was removed by the user */
        $toDelete = array_diff($existingIds, $submittedItemIds);

        $rawItemIds  = $request->input('item_id', []);
        $totalAmount = 0;

        foreach ($validated['product_id'] as $i => $productId) {
            $qty    = (int)   $validated['qty'][$i];
            $price  = (float) $validated['price'][$i];
            $total  = $qty * $price;
            $totalAmount += $total;

            $itemId = isset($rawItemIds[$i]) && $rawItemIds[$i] !== ''
                      ? (int) $rawItemIds[$i]
                      : null;

            if ($itemId && in_array($itemId, $existingIds)) {
                /* ── Update the existing order_item row ── */
                OrderItem::where('id', $itemId)->update([
                    'product_id'  => $productId,
                    'qty'         => $qty,
                    'unit_price'  => $price,
                    'total_price' => $total,
                ]);
            } else {
                /* ── Insert a brand-new order_item row ── */
                $order->items()->create([
                    'product_id'  => $productId,
                    'qty'         => $qty,
                    'unit_price'  => $price,
                    'total_price' => $total,
                ]);
            }
        }

        /* Soft-delete only the rows the user removed from the form */
        if (!empty($toDelete)) {
            OrderItem::whereIn('id', $toDelete)->delete();
        }

        $order->update([
            'unique_order_id'     => $validated['unique_order_id'],
            'broker_id'           => $validated['broker_id'],
            'brand_id'            => $validated['brand_id'],
            'dealer_id'           => $validated['dealer_id'],
            'order_date'          => $validated['order_date'],
            'delivery_address'    => $validated['delivery_address'],
            'payment_status'      => $validated['payment_status'],
            'partial_paid_amount' => $validated['payment_status'] === 'partial'
                                     ? $validated['partial_paid_amount'] : null,
            'total_order_amount'  => $totalAmount,
            'grand_total'         => $totalAmount,
        ]);

        return redirect()->route('order.index')
                         ->with('success', 'Order updated successfully.');
    }

    /* ------------------------------------------------------------------ */
    /*  DESTROY                                                             */
    /* ------------------------------------------------------------------ */
    public function destroy(OrderManagement $order)
    {
        $order->items()->delete();  // soft-delete line items first
        $order->delete();

        return redirect()->route('order.index')
                         ->with('success', 'Order deleted successfully.');
    }

    /* ------------------------------------------------------------------ */
    /*  LAST ITEM PRICE (AJAX)                                              */
    /* ------------------------------------------------------------------ */
    public function lastItemPrice(Request $request)
    {
        $dealerId  = $request->input('dealer_id');
        $productId = $request->input('product_id');

        if (!$dealerId || !$productId) {
            return response()->json(['price' => null]);
        }

        $lastItem = OrderItem::whereHas('order', function ($q) use ($dealerId) {
                $q->where('dealer_id', $dealerId);
            })
            ->where('product_id', $productId)
            ->latest()
            ->first();

        return response()->json([
            'price' => $lastItem ? number_format((float) $lastItem->unit_price, 2) : null,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  BULK DELETE                                                         */
    /* ------------------------------------------------------------------ */
    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;
        if (empty($ids)) {
            return response()->json(['message' => 'No records selected.'], 400);
        }

        $orders = OrderManagement::whereIn('id', $ids)->get();
        foreach ($orders as $order) {
            $order->items()->delete();
            $order->delete();
        }

        return response()->json(['message' => 'Selected orders deleted successfully.']);
    }

    /* ------------------------------------------------------------------ */
    /*  SHARED VALIDATION                                                   */
    /* ------------------------------------------------------------------ */
    private function validateOrder(Request $request, ?int $ignoreId = null): array
    {
        $uniqueRule = 'required|string|unique:order_management,unique_order_id'
                    . ($ignoreId ? ",$ignoreId" : '');

        return $request->validate([
            'unique_order_id'     => $uniqueRule,
            'broker_id'           => 'required|exists:users,id',
            'brand_id'            => 'required|exists:brand_management,id',
            'dealer_id'           => 'required|exists:dealer_management,id',
            'order_date'          => 'required|date',
            'delivery_address'    => 'required|string',
            'payment_status'      => 'required|in:unpaid,paid,partial',
            'partial_paid_amount' => 'nullable|numeric|min:0|required_if:payment_status,partial',
            'product_id'          => 'required|array|min:1',
            'product_id.*'        => 'required|exists:products,id',
            'qty'                 => 'required|array|min:1',
            'qty.*'               => 'required|integer|min:1',
            'price'               => 'required|array|min:1',
            'price.*'             => 'required|numeric|min:0',
        ], [
            'broker_id.required'              => 'Please select a broker.',
            'brand_id.required'               => 'Please select a brand.',
            'dealer_id.required'              => 'Please select a dealer.',
            'order_date.required'             => 'Please select an order date.',
            'delivery_address.required'       => 'Delivery address is required.',
            'payment_status.required'         => 'Please select a payment status.',
            'partial_paid_amount.required_if' => 'Please enter the paid amount.',
            'product_id.required'             => 'At least one product is required.',
            'product_id.*.required'           => 'Please select a product.',
            'qty.*.required'                  => 'Quantity is required.',
            'qty.*.min'                       => 'Quantity must be at least 1.',
            'price.*.required'                => 'Unit price is required.',
            'price.*.min'                     => 'Unit price cannot be negative.',
        ]);
    }
}
