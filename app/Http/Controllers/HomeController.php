<?php

namespace App\Http\Controllers;

use App\Exports\RawMaterialDailySummaryExport;
use App\Models\DealerManagement;
use App\Models\DispatchManagement;
use App\Models\OrderManagement;
use App\Models\User;
use App\Services\RawMaterial\RawMaterialDailySummaryService;
use App\Support\SalesScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class HomeController extends Controller
{
    public function __construct(
        protected RawMaterialDailySummaryService $rawMaterialDailySummaryService
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

        $data['rm_daily_summary'] = null;
        $data['rm_summary_materials'] = collect();
        $data['rm_material_filter'] = 'all';
        $data['rm_date_from'] = null;
        $data['rm_date_to'] = null;

        if ($loginUser->can('raw-material-daily-summary')) {
            $filters = $this->dailySummaryFilters($request);

            $summary = $this->rawMaterialDailySummaryService->build(
                $filters['material_id'],
                $filters['date_from'],
                $filters['date_to']
            );

            $data['rm_daily_summary'] = $summary;
            $data['rm_summary_materials'] = $summary['materials'];
            $data['rm_material_filter'] = $filters['material_filter'];
            $data['rm_date_from'] = $filters['date_from'];
            $data['rm_date_to'] = $filters['date_to'];
        }

        return view('dashboard', $data);
    }

    public function exportRawMaterialDailySummary(Request $request): BinaryFileResponse|RedirectResponse
    {
        $filters = $this->dailySummaryFilters($request);

        $summary = $this->rawMaterialDailySummaryService->build(
            $filters['material_id'],
            $filters['date_from'],
            $filters['date_to']
        );

        if ($summary['rows']->isEmpty()) {
            return redirect()
                ->route('dashboard', $this->dailySummaryQueryParams($filters))
                ->with('error', 'No records found to export for the current filters.');
        }

        $filename = 'raw-material-daily-summary-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new RawMaterialDailySummaryExport($summary), $filename);
    }

    /**
     * @return array{
     *     material_id: ?int,
     *     material_filter: string,
     *     date_from: ?string,
     *     date_to: ?string
     * }
     */
    protected function dailySummaryFilters(Request $request): array
    {
        $materialFilter = $request->query('rm_material_id', 'all');
        $materialId = ($materialFilter !== 'all' && $materialFilter !== null && $materialFilter !== '')
            ? (int) $materialFilter
            : null;

        $dateFrom = $this->normalizeSummaryDate($request->query('rm_date_from'));
        $dateTo = $this->normalizeSummaryDate($request->query('rm_date_to'));

        if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [
            'material_id'     => $materialId,
            'material_filter' => $materialId ? (string) $materialId : 'all',
            'date_from'       => $dateFrom,
            'date_to'         => $dateTo,
        ];
    }

    /**
     * @param  array{material_id: ?int, material_filter: string, date_from: ?string, date_to: ?string}  $filters
     * @return array<string, string>
     */
    protected function dailySummaryQueryParams(array $filters): array
    {
        $params = [];

        if ($filters['material_filter'] !== 'all') {
            $params['rm_material_id'] = $filters['material_filter'];
        }

        if ($filters['date_from']) {
            $params['rm_date_from'] = $filters['date_from'];
        }

        if ($filters['date_to']) {
            $params['rm_date_to'] = $filters['date_to'];
        }

        return $params;
    }

    protected function normalizeSummaryDate(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
