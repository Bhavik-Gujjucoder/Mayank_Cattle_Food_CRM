<?php

namespace App\Http\Controllers;

use App\Models\DispatchManagement;
use App\Models\OrderItem;
use App\Models\OrderManagement;
use App\Models\Truck;
use App\Models\User;
use App\Support\SalesScope;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class DispatchManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view-dispatch')->only(['index']);
        $this->middleware('permission:add-dispatch')->only(['create']);
        $this->middleware('permission:edit-dispatch')->only(['edit']);
    }

    /* ------------------------------------------------------------------ */
    /*  INDEX  — all dispatch records, with optional order filter         */
    /* ------------------------------------------------------------------ */
    public function index(Request $request)
    {
        /* Orders that have at least one dispatch — used to populate filter */
        $data['page_title'] = 'Dispatch Management';
        $data['orders']     = SalesScope::scopeOrders(
            OrderManagement::has('dispatches')->orderBy('unique_order_id')
        )->get(['id', 'unique_order_id']);

        if ($request->ajax()) {
            $query = SalesScope::scopeDispatches(
                DispatchManagement::with([
                    'order.items.dispatches',   /* needed for is_complete check */
                    'orderItem.product',
                    'transporter',
                ])
            )->latest();

            /* Order filter */
            if ($request->filled('order_id') && $request->order_id !== 'all') {
                $query->where('order_id', $request->order_id);
            }

            return DataTables::of($query)
                ->addIndexColumn()

                /*
                 * unique_order_id is a relationship value, not a real column on
                 * dispatch_management. Tell Yajra to ORDER BY order_id (the FK)
                 * when the user clicks that column header — reliable proxy sort.
                 */
                ->orderColumn('unique_order_id', 'order_id $1')

                /* Order ID — clickable link to the history page */
                ->addColumn('unique_order_id', function ($row) {
                    $url = route('dispatch.orderHistory', $row->order_id);
                    $id  = $row->order?->unique_order_id ?? '—';

                    /* Is every item in this order fully dispatched? */
                    $isComplete = $row->order
                        && $row->order->items->isNotEmpty()
                        && $row->order->items->every(fn($item) =>
                            (int) $item->dispatches->sum('no_of_bags') >= (int) $item->qty
                        );

                    $chip = $isComplete
                        ? ' <span class="dispatch-complete-chip"><i class="ti ti-circle-check"></i> Complete</span>'
                        : '';

                    return '<a href="' . $url . '" class="fw-semibold text-primary">' . e($id) . '</a>' . $chip;
                })

                ->addColumn('product_name',    fn($row) => $row->orderItem?->product?->name ?? '—')
                ->addColumn('transporter_name',fn($row) => $row->transporter?->name ?? '—')
                ->editColumn('dispatch_date',  fn($row) => $row->dispatch_date?->format('d M Y') ?? '—')
                ->addColumn('dealer_name',    fn($row) => $row->orderItem?->order?->dealer?->user?->name ?? '—')
                ->addColumn('status',         fn($row) => $row->statusBadge())

                /* 1/0 flag — used by DataTables createdRow to highlight complete rows */
                ->addColumn('is_complete', function ($row) {
                    if (!$row->order || $row->order->items->isEmpty()) return 0;
                    return $row->order->items->every(fn($item) =>
                        (int) $item->dispatches->sum('no_of_bags') >= (int) $item->qty
                    ) ? 1 : 0;
                })

                /* Action dropdown */
                ->addColumn('action', function ($row) {
                    $historyUrl = route('dispatch.orderHistory', $row->order_id);
                    // $editUrl    = $historyUrl . '?edit=' . $row->id;

                    $btn  = '<div class="dropdown table-action">
                                 <a href="#" class="action-icon" data-bs-toggle="dropdown" aria-expanded="false">
                                     <i class="fa fa-ellipsis-v"></i>
                                 </a>
                                 <div class="dropdown-menu dropdown-menu-right">';
                    $btn .= '<a href="' . $historyUrl . '" class="dropdown-item">
                                 <i class="ti ti-history text-info me-1"></i> View History
                             </a>';
                    // if (auth()->user()->can('edit-dispatch')) {
                    //     $btn .= '<a href="' . $editUrl . '" class="dropdown-item">
                    //                  <i class="ti ti-edit text-warning me-1"></i> Edit
                    //              </a>';
                    // }
                    $btn .= '</div></div>';
                    
                    $btn = auth()->user()->canAny(['view-dispatch']) ? $btn : '';
                    return $btn;
                })

                ->rawColumns(['unique_order_id', 'action', 'is_complete', 'status'])
                ->make(true);
        }

        return view('dispatch_management.index', $data);
    }

    /* ------------------------------------------------------------------ */
    /*  ORDER HISTORY  — dispatch history for one specific order          */
    /* ------------------------------------------------------------------ */
    public function orderHistory(OrderManagement $order)
    {
        SalesScope::authorizeOrderAccess($order);

        $data['page_title']   = 'Dispatch History';
        $data['order']        = $order->load([
            'dealer.user',
            'items.product',
            'items.dispatches.product',
            'items.dispatches.transporter',
        ]);
        $data['transporters'] = User::whereHas('roles', fn($q) => $q->where('name', 'transporter'))
                                    ->orderBy('name')
                                    ->get();

        /* ── Sequential dispatch eligibility ──────────────────────────
           Find the first prior order (same dealer, smaller id) that is
           not yet fully dispatched. Pass the result to the view so it
           can render a blocked-dispatch warning and disable the Add form.
        ──────────────────────────────────────────────────────────────── */
        $blockingOrder = OrderManagement::where('dealer_id', $order->dealer_id)
            ->where('id', '<', $order->id)
            ->orderBy('id')
            ->with(['items.dispatches', 'items.product'])
            ->get()
            ->first(fn($o) => ! $o->isFullyDispatched());

        $data['dispatchBlocked'] = $blockingOrder !== null;
        $data['blockingOrder']   = $blockingOrder;

        return view('dispatch_management.history', $data);
    }

    /* ------------------------------------------------------------------ */
    /*  STORE                                                             */
    /* ------------------------------------------------------------------ */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id'       => 'required|exists:order_management,id',
            'order_item_id'  => 'required|exists:order_items,id',
            'product_id'     => 'required|exists:products,id',
            'no_of_bags'     => 'required|integer|min:1',
            'dispatch_date'  => 'required|date',
            'transport_id'   => 'required|exists:users,id',
            'truck_number'   => 'required|string|max:100',
            'driver_contact' => 'required|string|max:20',
            'status'         => 'required|in:0,1',
        ], [
            'order_item_id.required'  => 'Please select a product.',
            'no_of_bags.required'     => 'No of bags/ton is required.',
            'no_of_bags.min'          => 'No of bags/ton must be at least 1.',
            // 'no_of_bags.max'          => 'The entered quantity cannot exceed the pending quantity.',
            'dispatch_date.required'   => 'Dispatch date is required.',
            'transport_id.required'   => 'Please select a transporter.',
            'transport_id.exists'     => 'Selected transporter is invalid.',
            'truck_number.required'   => 'Truck number is required.',
            'driver_contact.required' => 'Driver contact is required.',
            'status.required'         => 'Please select a payment status.',
            'status.in'               => 'Invalid payment status selected.',
        ]);

        /* Guard against over-dispatch */
        $orderItem  = OrderItem::findOrFail($validated['order_item_id']);
        $dispatched = (int) $orderItem->dispatches()->sum('no_of_bags');
        $pending    = max(0, (int) $orderItem->qty - $dispatched);

        if ((int) $validated['no_of_bags'] > $pending) {
            return $this->dispatchStoreErrorResponse($request, [
                'no_of_bags' => 'The entered quantity cannot exceed the pending quantity.',
            ]);
        }

        /* ── Sequential dispatch guard ────────────────────────────────
           Prevent saving a dispatch entry if any earlier order for the
           same dealer has not yet been fully dispatched.
           This catches direct-URL / API attempts that bypass the JS popup.
        ──────────────────────────────────────────────────────────────── */
        $parentOrder   = OrderManagement::findOrFail($validated['order_id']);
        SalesScope::authorizeOrderAccess($parentOrder);

        $blockingPrior = OrderManagement::where('dealer_id', $parentOrder->dealer_id)
            ->where('id', '<', $parentOrder->id)
            ->orderBy('id')
            ->with(['items.dispatches'])
            ->get()
            ->first(fn($o) => ! $o->isFullyDispatched());

        if ($blockingPrior) {
            return $this->dispatchStoreErrorResponse($request, [
                'order_item_id' => 'Order ' . $blockingPrior->unique_order_id
                    . ' must be fully dispatched before dispatching this order.',
            ]);
        }

        DispatchManagement::create($validated);

        return redirect()
            ->route('dispatch.orderHistory', $validated['order_id'])
            ->with('success', 'Dispatch entry added successfully.');
    }

    /* ------------------------------------------------------------------ */
    /*  DESTROY                                                           */
    /* ------------------------------------------------------------------ */
    public function destroy(DispatchManagement $dispatch)
    {
        SalesScope::authorizeDispatchAccess($dispatch);

        $orderId = $dispatch->order_id;
        $dispatch->delete();

        return redirect()
            ->route('dispatch.orderHistory', $orderId)
            ->with('success', 'Dispatch entry deleted successfully.');
    }

    /* ------------------------------------------------------------------ */
    /*  UPDATE                                                            */
    /* ------------------------------------------------------------------ */
    public function update(Request $request, DispatchManagement $dispatch)
    {
        SalesScope::authorizeDispatchAccess($dispatch);

        $validated = $request->validate([
            'no_of_bags'     => 'required|integer|min:1',
            'dispatch_date'  => 'required|date',
            'transport_id'   => 'required|exists:users,id',
            'truck_number'   => 'required|string|max:100',
            'driver_contact' => 'required|string|max:20',
            'status'         => 'required|in:0,1',
        ], [
            'no_of_bags.required'     => 'No of bags/ton is required.',
            'no_of_bags.min'          => 'No of bags/ton must be at least 1.',
            'dispatch_date.required'  => 'Dispatch date is required.',
            'transport_id.required'   => 'Please select a transporter.',
            'transport_id.exists'     => 'Selected transporter is invalid.',
            'truck_number.required'   => 'Truck number is required.',
            'driver_contact.required' => 'Driver contact is required.',
            'status.required'         => 'Please select a payment status.',
            'status.in'               => 'Invalid payment status selected.',
        ]);

        /*
         * Over-dispatch guard:
         * Sum every OTHER dispatch for this order item (exclude the one being
         * edited), then ensure the new bag count fits within what's left.
         */
        $orderItem        = $dispatch->orderItem;
        $otherDispatched  = (int) $orderItem->dispatches()
                                ->where('id', '!=', $dispatch->id)
                                ->sum('no_of_bags');
        $effectivePending = max(0, (int) $orderItem->qty - $otherDispatched);

        if ((int) $validated['no_of_bags'] > $effectivePending) {
            return back()
                ->withInput()
                ->withErrors(['no_of_bags' => 'The entered quantity cannot exceed the pending quantity.'])
                ->with('edit_dispatch_id', $dispatch->id);
        }

        $dispatch->update($validated);

        return redirect()
            ->route('dispatch.orderHistory', $dispatch->order_id)
            ->with('success', 'Dispatch entry updated successfully.');
    }

    /* ------------------------------------------------------------------ */
    /*  AJAX — order items for dashboard dispatch modal                   */
    /* ------------------------------------------------------------------ */
    public function getOrderDispatchFormData(OrderManagement $order)
    {
        SalesScope::authorizeOrderAccess($order);

        $order->load(['items.product', 'items.dispatches']);

        $blockingOrder = OrderManagement::where('dealer_id', $order->dealer_id)
            ->where('id', '<', $order->id)
            ->orderBy('id')
            ->with(['items.dispatches', 'items.product'])
            ->get()
            ->first(fn ($o) => ! $o->isFullyDispatched());

        $items = $order->items->map(function ($item) {
            $pending = $item->pendingQty();

            return [
                'id'           => $item->id,
                'product_id'   => $item->product_id,
                'product_name' => $item->product?->name ?? '—',
                'qty'          => (int) $item->qty,
                'pending'      => $pending,
                'disabled'     => $pending <= 0,
            ];
        })->values();

        if ($blockingOrder) {
            $pendingItems = $blockingOrder->items
                ->map(function ($item) {
                    $dispatched = (int) $item->dispatches->sum('no_of_bags');
                    $pending    = max(0, (int) $item->qty - $dispatched);

                    return [
                        'product_name'   => $item->product?->name ?? '—',
                        'ordered_qty'    => (int) $item->qty,
                        'dispatched_qty' => $dispatched,
                        'pending_qty'    => $pending,
                    ];
                })
                ->filter(fn ($i) => $i['pending_qty'] > 0)
                ->values();

            return response()->json([
                'eligible'       => false,
                'items'          => [],
                'blocking_order' => [
                    'unique_order_id' => $blockingOrder->unique_order_id,
                    'order_date'      => $blockingOrder->order_date?->format('d M Y') ?? '—',
                    'history_url'     => route('dispatch.orderHistory', $blockingOrder->id),
                    'pending_items'   => $pendingItems,
                ],
            ]);
        }

        return response()->json([
            'eligible' => true,
            'items'    => $items,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  AJAX — trucks for a transporter (used by truck dropdown)          */
    /* ------------------------------------------------------------------ */
    public function getTrucksByTransporter(User $transporter)
    {
        $trucks = Truck::where('transporter_id', $transporter->id)
            ->where('status', 1)
            ->orderBy('truck_number')
            ->get(['id', 'truck_number']);

        return response()->json([
            'trucks' => $trucks,
            'phone'  => $transporter->phone_no ?? '',
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  HELPERS                                                           */
    /* ------------------------------------------------------------------ */
    private function dispatchStoreErrorResponse(Request $request, array $errors)
    {
        $redirect = back()->withInput()->withErrors($errors);

        if ($request->input('from_dashboard')) {
            $redirect->with('open_dashboard_dispatch_modal', true);
        }

        return $redirect;
    }

    /* ------------------------------------------------------------------ */
    /*  STUBS                                                             */
    /* ------------------------------------------------------------------ */
    public function create() {}
    public function show(DispatchManagement $dispatch) {}
    public function edit(DispatchManagement $dispatch) {}
}
