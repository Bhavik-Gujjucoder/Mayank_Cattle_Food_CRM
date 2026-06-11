<?php

namespace App\Http\Controllers;

use App\Models\DealerManagement;
use App\Models\DispatchManagement;
use App\Models\OrderManagement;
use App\Models\User;
use App\Support\SalesScope;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /* ------------------------------------------------------------------ */
    /*  DASHBOARD                                                         */
    /* ------------------------------------------------------------------ */
    public function dashboard(Request $request)
    {

        $data['login_user']       = auth()->user();
        $data['role']             = $data['login_user']->roles->first()->name ?? '';
        $data['user_name']        = $data['login_user']->name;
        $data['page_title']       = ucfirst($data['role']) . ' Dashboard';
        $data['dealers']          = DealerManagement::whereHas('user', function ($q) {
            $q->where('status', 1);
        })->orderBy('id', 'desc')->get();
        $data['brokers']          = User::whereHas('roles', function ($q) {
            $q->where('name', 'broker');
        })->orderBy('id', 'desc')->get();
        $data['transporters']     = User::whereHas('roles', function ($q) {
            $q->where('name', 'transporter');
        })->orderBy('id', 'desc')->get();

        /* ── Recent Soda/Orders & dispatches — SalesScope (role-based) ── */
        $loginUser = $data['login_user'];

        $data['soda_order'] = SalesScope::scopeOrders(OrderManagement::query())
            ->latest()
            ->take(5)
            ->get();

        $data['total_dealers']    = $data['dealers']->count();
        $data['total_broker']     = $data['brokers']->count();
        $data['total_soda_order'] = SalesScope::scopeOrders(OrderManagement::query())->count();

        $data['dispatch_order'] = SalesScope::scopeDispatches(DispatchManagement::query())
            ->latest()
            ->take(5)
            ->get();
        $data['total_dispatch_order'] = SalesScope::scopeDispatches(DispatchManagement::query())->count();

        // $data['total_dispatch_order'] = $data['role'] == 'broker' ? $data['dispatch_order']->where('broker_id', $data['login_user']->id)->count() : $data['dispatch_order']->count();

        /* ── Orders for dashboard dispatch modal (not fully dispatched) ── */
        $data['dispatch_form_orders'] = collect();
        if ($loginUser->can('add-dispatch')) {
            $orderQuery = SalesScope::scopeOrders(
                OrderManagement::with(['items.dispatches', 'dealer.user'])->orderBy('id', 'desc')
            );

            $data['dispatch_form_orders'] = $orderQuery->get()
                ->filter(fn ($o) => ! $o->isFullyDispatched())
                ->values();
        }

        return view('dashboard', $data);
    }
}
