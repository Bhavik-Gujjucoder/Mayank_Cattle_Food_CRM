<?php

namespace App\Http\Controllers;

use App\Models\DealerManagement;
use App\Models\DispatchManagement;
use App\Models\OrderManagement;
use App\Models\User;
use App\Services\DeliveryPendingPaymentsReportService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(
        protected DeliveryPendingPaymentsReportService $pendingPaymentsReportService
    ) {}

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

        /* ── Recent Soda/Orders — role-based filter ──────────────────────
         *
         *  Broker  → orders where broker_id = logged-in user
         *  Dealer  → orders belonging to this dealer
         *            (order_management.dealer_id → dealer_management.id
         *             → traced back via dealer_management.user_id)
         *  Others  → latest 5 records across all orders (default)
         *
         * ---------------------------------------------------------------- */
        $loginUser = $data['login_user'];

        if ($data['role'] === 'broker') {

            $data['soda_order'] = OrderManagement::where('broker_id', $loginUser->id)
                ->latest()->take(5)->get();
        } elseif ($data['role'] === 'dealer') {

            /* dealer_id on order_management is dealer_management.id, not users.id.
               Use whereHas to trace: order → dealer (dealer_management) → user_id */
            $data['soda_order'] = OrderManagement::whereHas('dealer', function ($q) use ($loginUser) {
                $q->where('user_id', $loginUser->id);
            })->latest()->take(5)->get();
        } else {

            $data['soda_order'] = OrderManagement::latest()->take(5)->get();
        }

        $data['total_dealers']    = $data['dealers']->count();
        $data['total_broker']     = $data['brokers']->count();

        /* Total count computed with a dedicated query — not from the take(5)
           collection above — so the count widget always shows the correct total. */
        if ($data['role'] === 'broker') {
            $data['total_soda_order'] = OrderManagement::where('broker_id', $loginUser->id)->count();
        } elseif ($data['role'] === 'dealer') {
            $data['total_soda_order'] = OrderManagement::whereHas('dealer', function ($q) use ($loginUser) {
                $q->where('user_id', $loginUser->id);
            })->count();
        } else {
            $data['total_soda_order'] = OrderManagement::count();
        }

        /* ── Recent Dispatch Request — role-based filter ──────────────────
         *
         *  Broker  → dispatches for orders where broker_id = logged-in user
         *  Dealer  → dispatches for orders belonging to this dealer
         *            (order_management.dealer_id → dealer_management.id
         *             → traced back via dealer_management.user_id)
         *  Others  → latest 5 records across all dispatches (default)
         *
         * ---------------------------------------------------------------- */
        if ($data['role'] === 'broker') {

            $data['dispatch_order'] = DispatchManagement::whereHas('order', function ($q) use ($loginUser) {
                $q->where('broker_id', $loginUser->id);
            })->latest()->take(5)->get();
            $data['total_dispatch_order'] = $data['dispatch_order']->count();
        } elseif ($data['role'] === 'dealer') {

            /* dealer_id on order_management is dealer_management.id, not users.id.
               Use nested whereHas to trace: dispatch → order → dealer → user_id */
            $data['dispatch_order'] = DispatchManagement::whereHas('order', function ($q) use ($loginUser) {
                $q->whereHas('dealer', function ($q2) use ($loginUser) {
                    $q2->where('user_id', $loginUser->id);
                });
            })->latest()->take(5)->get();
            $data['total_dispatch_order'] = $data['dispatch_order']->count();
        } else {

            $data['dispatch_order'] = DispatchManagement::latest()->take(5)->get();
            $data['total_dispatch_order'] = $data['dispatch_order']->count();
        }

        // $data['total_dispatch_order'] = $data['role'] == 'broker' ? $data['dispatch_order']->where('broker_id', $data['login_user']->id)->count() : $data['dispatch_order']->count();

        /* ── Orders for dashboard dispatch modal (not fully dispatched) ── */
        $data['dispatch_form_orders'] = collect();
        if ($loginUser->can('add-dispatch')) {
            $orderQuery = OrderManagement::with(['items.dispatches', 'dealer.user'])
                ->orderBy('id', 'desc');

            if ($data['role'] === 'broker') {
                $orderQuery->where('broker_id', $loginUser->id);
            } elseif ($data['role'] === 'dealer') {
                $orderQuery->whereHas('dealer', function ($q) use ($loginUser) {
                    $q->where('user_id', $loginUser->id);
                });
            }

            $data['dispatch_form_orders'] = $orderQuery->get()
                ->filter(fn ($o) => ! $o->isFullyDispatched())
                ->values();
        }

        $data['dpp_dashboard_sections'] = collect();
        $data['dpp_dashboard_summary']  = [
            'order_count'    => 0,
            'dispatch_count' => 0,
            'brand_count'    => 0,
        ];
        $data['dpp_dashboard_can_link_order'] = false;
        $data['dpp_dashboard_min_days']       = DeliveryPendingPaymentsReportService::DASHBOARD_MIN_PENDING_DAYS;

        if ($loginUser->can('view-dispatch-pending-payments')) {
            $data['dpp_dashboard_sections'] = $this->pendingPaymentsReportService->buildForDashboard();
            $data['dpp_dashboard_summary']  = $this->pendingPaymentsReportService->summarize(
                $data['dpp_dashboard_sections']
            );
            $data['dpp_dashboard_can_link_order'] = $loginUser->can('add-dispatch')
                || $loginUser->can('edit-dispatch')
                || $loginUser->can('delete-dispatch');
        }

        return view('dashboard', $data);
    }
}
