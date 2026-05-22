<?php

namespace App\Http\Controllers;

use App\Models\DispatchManagement;
use App\Models\OrderItem;
use App\Models\OrderManagement;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class DispatchManagementController extends Controller
{
    /* ------------------------------------------------------------------ */
    /*  INDEX  — all dispatch records, with optional order filter         */
    /* ------------------------------------------------------------------ */
    public function index(Request $request)
    {
        /* Orders that have at least one dispatch — used to populate filter */
        $data['page_title'] = 'Dispatch Management';
        $data['orders']     = OrderManagement::has('dispatches')
                                ->orderBy('unique_order_id')
                                ->get(['id', 'unique_order_id']);

        if ($request->ajax()) {
            $query = DispatchManagement::with([
                'order.items.dispatches',   /* needed for is_complete check */
                'orderItem.product',
                'transporter',
            ])->latest();

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
                    return $btn;
                })

                ->rawColumns(['unique_order_id', 'action', 'is_complete'])
                ->make(true);
        }

        return view('dispatch_management.index', $data);
    }

    /* ------------------------------------------------------------------ */
    /*  ORDER HISTORY  — dispatch history for one specific order          */
    /* ------------------------------------------------------------------ */
    public function orderHistory(OrderManagement $order)
    {
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
        ]);

        /* Guard against over-dispatch */
        $orderItem  = OrderItem::findOrFail($validated['order_item_id']);
        $dispatched = (int) $orderItem->dispatches()->sum('no_of_bags');
        $pending    = max(0, (int) $orderItem->qty - $dispatched);

        if ((int) $validated['no_of_bags'] > $pending) {
            return back()
                ->withInput()
                ->withErrors([
                    'no_of_bags' => 'The entered quantity cannot exceed the pending quantity.',
                ]);
        }

        /* ── Sequential dispatch guard ────────────────────────────────
           Prevent saving a dispatch entry if any earlier order for the
           same dealer has not yet been fully dispatched.
           This catches direct-URL / API attempts that bypass the JS popup.
        ──────────────────────────────────────────────────────────────── */
        $parentOrder   = OrderManagement::findOrFail($validated['order_id']);
        $blockingPrior = OrderManagement::where('dealer_id', $parentOrder->dealer_id)
            ->where('id', '<', $parentOrder->id)
            ->orderBy('id')
            ->with(['items.dispatches'])
            ->get()
            ->first(fn($o) => ! $o->isFullyDispatched());

        if ($blockingPrior) {
            return back()
                ->withInput()
                ->withErrors([
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
        $validated = $request->validate([
            'no_of_bags'     => 'required|integer|min:1',
            'dispatch_date'  => 'required|date',
            'transport_id'   => 'required|exists:users,id',
            'truck_number'   => 'required|string|max:100',
            'driver_contact' => 'required|string|max:20',
        ], [
            'no_of_bags.required'     => 'No of bags/ton is required.',
            'no_of_bags.min'          => 'No of bags/ton must be at least 1.',
            'dispatch_date.required'  => 'Dispatch date is required.',
            'transport_id.required'   => 'Please select a transporter.',
            'transport_id.exists'     => 'Selected transporter is invalid.',
            'truck_number.required'   => 'Truck number is required.',
            'driver_contact.required' => 'Driver contact is required.',
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
    /*  STUBS                                                             */
    /* ------------------------------------------------------------------ */
    public function create() {}
    public function show(DispatchManagement $dispatch) {}
    public function edit(DispatchManagement $dispatch) {}
}
