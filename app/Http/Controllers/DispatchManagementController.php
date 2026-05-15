<?php

namespace App\Http\Controllers;

use App\Models\DispatchManagement;
use App\Models\OrderItem;
use App\Models\OrderManagement;
use App\Models\User;
use Illuminate\Http\Request;

class DispatchManagementController extends Controller
{
    /* ------------------------------------------------------------------ */
    /*  INDEX  (general listing — kept as stub)                           */
    /* ------------------------------------------------------------------ */
    public function index()
    {
        $data['page_title'] = 'Dispatch Management';
        return view('dispatch_management.index', $data);
    }

    /* ------------------------------------------------------------------ */
    /*  ORDER HISTORY  — dispatch history for one specific order          */
    /* ------------------------------------------------------------------ */
    public function orderHistory(OrderManagement $order)
    {
        $data['page_title']   = 'Dispatch History';
        $data['order']        = $order->load([
            'items.product',
            'items.dispatches.product',
            'items.dispatches.transporter',
        ]);
        $data['transporters'] = User::whereHas('roles', fn($q) => $q->where('name', 'transporter'))
                                    ->orderBy('name')
                                    ->get();

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
