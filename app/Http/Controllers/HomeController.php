<?php

namespace App\Http\Controllers;

use App\Exports\RawMaterialDailySummaryExport;
use App\Models\DealerManagement;
use App\Models\DispatchManagement;
use App\Models\OrderManagement;
use App\Models\RawMaterial;
use App\Models\RawMaterialOrder;
use App\Models\RawMaterialReceive;
use App\Models\User;
use App\Services\RawMaterial\RawMaterialDailySummaryService;
use App\Services\RawMaterialCacheService;
use App\Services\SequentialDispatchService;
use App\Support\ProductUnit;
use App\Support\SalesScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\DataTables;

class HomeController extends Controller
{
    private const DASHBOARD_RECENT_LIMIT = 10;

    public function __construct(
        protected RawMaterialDailySummaryService $rawMaterialDailySummaryService,
        protected SequentialDispatchService $sequentialDispatch
    ) {}

    /* ------------------------------------------------------------------ */
    /*  DASHBOARD                                                         */
    /* ------------------------------------------------------------------ */
    public function dashboard(Request $request)
    {
        $loginUser = $request->user();

        $data['login_user'] = $loginUser;
        $data['role']       = $loginUser->roles->first()->name ?? '';
        $data['user_name']  = $loginUser->name;
        $data['page_title'] = ucfirst($data['role']) . ' Dashboard';

        $data['total_dealers'] = DealerManagement::whereHas('user', function ($q) {
            $q->where('status', 1);
        })->count();

        $data['total_broker'] = User::whereHas('roles', function ($q) {
            $q->where('name', 'broker');
        })->count();

        $data['total_soda_order'] = SalesScope::scopeOrders(OrderManagement::query())->count();
        $data['total_dispatch_order'] = SalesScope::scopeDispatches(DispatchManagement::query())->count();
        $data['total_raw_materials'] = RawMaterial::count();
        $data['total_raw_material_orders'] = RawMaterialOrder::count();

        $data['transporters'] = $loginUser->can('add-dispatch')
            ? User::whereHas('roles', fn ($q) => $q->where('name', 'transporter'))->orderBy('name')->get(['id', 'name'])
            : collect();

        $data['rm_daily_summary'] = null;
        $data['rm_summary_materials'] = collect();
        $data['rm_material_filter'] = 'all';
        $data['rm_date_from'] = null;
        $data['rm_date_to'] = null;

        if ($loginUser->can('raw-material-daily-summary')) {
            $filters = $this->dailySummaryFilters($request);
            $data['rm_summary_materials'] = $this->rawMaterialDailySummaryService->build(
                null,
                null,
                null
            )['materials'];
            $data['rm_material_filter'] = $filters['material_filter'];
            $data['rm_date_from'] = $filters['date_from'];
            $data['rm_date_to'] = $filters['date_to'];
            $data['rm_daily_summary'] = [
                'summary_date' => now()->startOfDay(),
            ];
        }

        return view('dashboard', $data);
    }

    public function dataDealers(Request $request)
    {
        abort_unless($request->user()->can('recent-dealers'), 403);

        $canEdit = $request->user()->can('edit-dealer');

        $dealers = DealerManagement::with(['user', 'city'])
            ->whereHas('user', fn ($q) => $q->where('status', 1))
            ->orderByDesc('id')
            ->limit(self::DASHBOARD_RECENT_LIMIT)
            ->get();

        return DataTables::of($dealers)
            ->addIndexColumn()
            ->addColumn('dealer_name', function ($row) use ($canEdit) {
                $name = e($row->user?->name ?? '—');

                return $canEdit
                    ? '<a href="' . route('dealer.edit', $row->id) . '">' . $name . '</a>'
                    : $name;
            })
            ->addColumn('city_name', fn ($row) => e($row->city?->city_name ?? '—'))
            ->rawColumns(['dealer_name'])
            ->make(true);
    }

    public function dataSodaOrders(Request $request)
    {
        abort_unless($request->user()->can('recent-soda-orders'), 403);

        $canEdit = $request->user()->can('edit-order');

        $orders = SalesScope::scopeOrders(
            OrderManagement::with(['dealer.user'])->latest()
        )
            ->limit(self::DASHBOARD_RECENT_LIMIT)
            ->get();

        return DataTables::of($orders)
            ->addIndexColumn()
            ->addColumn('order_ref', function ($row) use ($canEdit) {
                $id = e($row->unique_order_id ?? '—');

                return $canEdit
                    ? '<a href="' . route('order.edit', $row->id) . '" class="text-info">' . $id . '</a>'
                    : '<span class="text-info">' . $id . '</span>';
            })
            ->addColumn('dealer_name', function ($row) use ($canEdit) {
                $name = e($row->dealer?->user?->name ?? '—');

                return $canEdit
                    ? '<a href="' . route('order.edit', $row->id) . '">' . $name . '</a>'
                    : $name;
            })
            ->editColumn('order_date', fn ($row) => $row->order_date?->format('d M Y') ?? '—')
            ->rawColumns(['order_ref', 'dealer_name'])
            ->make(true);
    }

    public function dataDispatches(Request $request)
    {
        abort_unless($request->user()->can('recent-dispatch-request'), 403);

        $canEdit = $request->user()->can('edit-dispatch');

        $dispatches = SalesScope::scopeDispatches(
            DispatchManagement::with(['product', 'order'])->latest()
        )
            ->limit(self::DASHBOARD_RECENT_LIMIT)
            ->get();

        return DataTables::of($dispatches)
            ->addIndexColumn()
            ->addColumn('product_info', function ($row) use ($canEdit) {
                $name = e($row->product?->name ?? '—');
                $qty = e(ProductUnit::formatWithUnit($row->no_of_bags, $row->product?->unit));

                if ($canEdit) {
                    return '<a href="' . route('dispatch.orderHistory', $row->order_id) . '">'
                        . $name . ' <small class="text-info">(' . $qty . ')</small></a>';
                }

                return $name . ' <small class="text-info">(' . $qty . ')</small>';
            })
            ->addColumn('order_ref', function ($row) use ($canEdit) {
                $id = e($row->order?->unique_order_id ?? '—');

                return $canEdit
                    ? '<a href="' . route('dispatch.orderHistory', $row->order_id) . '" class="text-info">' . $id . '</a>'
                    : '<span class="text-info">' . $id . '</span>';
            })
            ->editColumn('dispatch_date', fn ($row) => $row->dispatch_date?->format('d M Y') ?? '—')
            ->rawColumns(['product_info', 'order_ref'])
            ->make(true);
    }

    public function dataRawMaterialOrders(Request $request)
    {
        abort_unless($request->user()->can('raw-material-orders'), 403);

        $canEdit = $request->user()->can('edit-raw-material-purchas-order');

        $orders = RawMaterialOrder::with(['supplierBroker', 'supplier'])
            ->latest()
            ->limit(self::DASHBOARD_RECENT_LIMIT)
            ->get();

        return DataTables::of($orders)
            ->addIndexColumn()
            ->addColumn('order_ref', function ($row) use ($canEdit) {
                $label = e($row->order_unique_id ?? '#' . $row->id);

                return $canEdit
                    ? '<a href="' . route('raw-material.order.show', $row->id) . '" class="text-info">' . $label . '</a>'
                    : $label;
            })
            ->addColumn('supplier_broker_name', fn ($row) => e($row->supplierBroker?->name ?? '—'))
            ->addColumn('supplier_name', fn ($row) => '<span class="fw-semibold">' . e($row->supplier?->name ?? '—') . '</span>')
            ->editColumn('order_date', fn ($row) => $row->order_date?->format('d M Y') ?? '—')
            ->editColumn('total_qty', fn ($row) => number_format($row->total_qty) . ' tons')
            ->rawColumns(['order_ref', 'supplier_name'])
            ->make(true);
    }

    public function dataRawMaterialReceives(Request $request)
    {
        abort_unless($request->user()->can('raw-material-received-onroad'), 403);

        $canEdit = $request->user()->can('edit-raw-material-purchas-order');

        $receives = RawMaterialReceive::with(['rawMaterial.category', 'order'])
            ->where('status', 0)
            ->latest()
            ->limit(self::DASHBOARD_RECENT_LIMIT)
            ->get();

        return DataTables::of($receives)
            ->addIndexColumn()
            ->addColumn('order_ref', function ($row) use ($canEdit) {
                $label = e($row->order?->order_unique_id ?? '#' . $row->id);

                return $canEdit
                    ? '<a href="' . route('raw-material.receive.edit', $row->id) . '" class="text-info">' . $label . '</a>'
                    : $label;
            })
            ->addColumn('supplier_order_id', fn ($row) => e($row->order?->supplier_order_id ?? '—'))
            ->addColumn('category_name', fn ($row) => '<span class="fw-semibold">' . e($row->rawMaterial?->category?->name ?? '—') . '</span>')
            ->addColumn('material_name', fn ($row) => e($row->rawMaterial?->name ?? '—'))
            ->editColumn('qty', fn ($row) => number_format($row->qty))
            ->addColumn('freight_html', fn ($row) => RawMaterialCacheService::receiveFreightHtml($row))
            ->editColumn('received_date', fn ($row) => $row->received_date?->format('d M Y') ?? '—')
            ->rawColumns(['order_ref', 'category_name', 'freight_html'])
            ->make(true);
    }

    public function dataRmDailySummary(Request $request)
    {
        abort_unless($request->user()->can('raw-material-daily-summary'), 403);

        $filters = $this->dailySummaryFilters($request);
        $summary = $this->rawMaterialDailySummaryService->build(
            $filters['material_id'],
            $filters['date_from'],
            $filters['date_to']
        );

        $canViewOrder = $request->user()->can('view-raw-material-purchas-order');

        return DataTables::of($summary['rows'])
            ->addIndexColumn()
            ->addColumn('order_date', fn ($row) => e(data_get($row, 'order_date', '—')))
            ->addColumn('supplier_broker_name', fn ($row) => e(data_get($row, 'supplier_broker_name', '—')))
            ->addColumn('material_name', fn ($row) => e(data_get($row, 'material_name', '—')))
            ->addColumn('party_name', function ($row) use ($canViewOrder) {
                $name = e(data_get($row, 'party_name', '—'));
                $orderId = (int) data_get($row, 'order_id', 0);

                if ($canViewOrder && $orderId > 0) {
                    return '<a href="' . route('raw-material.order.show', $orderId) . '" class="text-decoration-none">' . $name . '</a>';
                }

                return $name;
            })
            ->addColumn('total_qty_fmt', fn ($row) => number_format((int) data_get($row, 'total_qty', 0)))
            ->addColumn('on_road_qty_fmt', fn ($row) => number_format((int) data_get($row, 'on_road_qty', 0)))
            ->addColumn('unloading_qty_fmt', fn ($row) => number_format((int) data_get($row, 'unloading_qty', 0)))
            ->addColumn('pending_qty_fmt', fn ($row) => number_format((int) data_get($row, 'pending_qty', 0)))
            ->addColumn('rate_fmt', fn ($row) => number_format((float) data_get($row, 'rate', 0), 2))
            ->addColumn('average_fmt', fn ($row) => number_format((float) data_get($row, 'average', 0), 2))
            ->addColumn('pending_amount_fmt', fn ($row) => number_format((float) data_get($row, 'pending_amount', 0), 2))
            ->addColumn('received_amount_fmt', fn ($row) => number_format((float) data_get($row, 'received_amount', 0), 2))
            ->addColumn('freight_fmt', fn ($row) => number_format((float) data_get($row, 'freight', 0), 2))
            ->rawColumns(['party_name'])
            ->with([
                'totals'       => $summary['totals'],
                'summary_date' => $summary['summary_date']->format('d M Y'),
            ])
            ->make(true);
    }

    public function dataDispatchFormOrders(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('add-dispatch'), 403);

        $query = SalesScope::scopeOrders(
            OrderManagement::with(['dealer.user'])->orderByDesc('id')
        );

        $this->sequentialDispatch->scopeWithPendingDispatch($query);

        $orders = $query->limit(500)->get(['id', 'unique_order_id', 'dealer_id']);

        return response()->json([
            'orders' => $orders->map(fn (OrderManagement $order) => [
                'id'       => $order->id,
                'label'    => ($order->unique_order_id ?? '—') . ' — ' . ($order->dealer?->user?->name ?? '—'),
                'form_url' => route('dispatch.orderFormData', $order->id),
            ])->values(),
        ]);
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
