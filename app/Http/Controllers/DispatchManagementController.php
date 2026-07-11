<?php

namespace App\Http\Controllers;

use App\Models\DealerManagement;
use App\Models\DispatchManagement;
use App\Models\OrderItem;
use App\Models\OrderManagement;
use App\Models\Product;
use App\Models\Truck;
use App\Models\User;
use App\Support\FinancialYear;
use App\Support\ProductUnit;
use App\Support\SalesScope;
use App\Services\PaymentReceivableService;
use App\Services\SequentialDispatchService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class DispatchManagementController extends Controller
{
    public function __construct(
        protected SequentialDispatchService $sequentialDispatch
    ) {
        $this->middleware('permission:view-dispatch')->only(['index', 'show']);
        $this->middleware('permission:add-dispatch')->only(['create']);
        $this->middleware('permission:edit-dispatch')->only(['edit']);
    }

    /* ------------------------------------------------------------------ */
    /*  INDEX  — all dispatch records, with optional order filter         */
    /* ------------------------------------------------------------------ */
    public function index(Request $request)
    {
        $data['page_title'] = 'Dispatch Management';

        $scopedOrdersQuery = SalesScope::scopeOrders(
            OrderManagement::has('dispatches')->orderBy('unique_order_id')
        );

        $data['orders'] = (clone $scopedOrdersQuery)->get(['id', 'unique_order_id', 'dealer_id']);

        $productIds = SalesScope::scopeDispatches(DispatchManagement::query())
            ->distinct()
            ->pluck('product_id');

        $data['products'] = Product::query()
            ->whereIn('id', $productIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $data['dealers'] = SalesScope::filterableDealers();

        if ($request->ajax()) {
            $canViewDispatch = auth()->user()->can('view-dispatch');

            $query = SalesScope::scopeDispatches(
                DispatchManagement::with([
                    'order.items.dispatches',   /* needed for is_complete check */
                    'orderItem.product',
                    'orderItem.order.dealer.user',
                    'transporter',
                ])
            )->latest();

            $query = $this->applyDispatchIndexFilters($query, $request);

            $receivableService = app(PaymentReceivableService::class);

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
                ->editColumn('no_of_bags', function ($row) {
                    $unit = $row->orderItem?->product?->unit;

                    return ProductUnit::formatWithUnit((int) $row->no_of_bags, $unit);
                })
                ->addColumn('transporter_name',fn($row) => $row->transporter?->name ?? '—')
                ->editColumn('dispatch_date',  fn($row) => $row->dispatch_date?->format('d M Y') ?? '—')
                ->addColumn('dealer_name',    fn($row) => $row->orderItem?->order?->dealer?->user?->name ?? '—')
                ->addColumn('late_fee', function ($row) use ($receivableService) {
                    $summary = $receivableService->summarizeDispatch($row);

                    return PaymentReceivableService::formatMoney($summary['accrued_late_fee']);
                })
                ->addColumn('balance_due', function ($row) use ($receivableService) {
                    return $receivableService->formatBalanceDueDisplay($row);
                })
                ->addColumn('status',         fn($row) => $row->statusBadge())

                /* 1/0 flag — used by DataTables createdRow to highlight complete rows */
                ->addColumn('is_complete', function ($row) {
                    if (!$row->order || $row->order->items->isEmpty()) return 0;
                    return $row->order->items->every(fn($item) =>
                        (int) $item->dispatches->sum('no_of_bags') >= (int) $item->qty
                    ) ? 1 : 0;
                })

                /* Action dropdown */
                ->addColumn('action', function ($row) use ($canViewDispatch) {
                    $canDelete = auth()->user()->can('delete-dispatch');

                    if (! $canViewDispatch && ! $canDelete) {
                        return '—';
                    }

                    $historyUrl  = route('dispatch.orderHistory', $row->order_id);
                    $productName   = $row->orderItem?->product?->name ?? '—';
                    $unit          = $row->orderItem?->product?->unit;
                    $qtyLabel      = ProductUnit::formatWithUnit((int) $row->no_of_bags, $unit);
                    $dispatchDate  = $row->dispatch_date?->format('d M Y') ?? '—';

                    $btn  = '<div class="dropdown table-action">
                                 <a href="#" class="action-icon" data-bs-toggle="dropdown" aria-expanded="false">
                                     <i class="fa fa-ellipsis-v"></i>
                                 </a>
                                 <div class="dropdown-menu dropdown-menu-right">';

                    if ($canViewDispatch) {
                        $btn .= '<a href="' . $historyUrl . '" class="dropdown-item">
                                     <i class="ti ti-history text-info me-1"></i> View History
                                 </a>';
                    }

                    if ($canDelete) {
                        $btn .= '<a href="javascript:void(0)" class="dropdown-item delete-dispatch-index-btn"
                                     data-id="' . $row->id . '"
                                     data-product-name="' . e($productName) . '"
                                     data-qty-label="' . e($qtyLabel) . '"
                                     data-dispatch-date="' . e($dispatchDate) . '">
                                     <i class="ti ti-trash text-danger me-1"></i> Delete
                                 </a>';
                        $btn .= '<form action="' . route('dispatch.destroy', $row->id) . '" method="POST"
                                      id="delete-dispatch-form-' . $row->id . '" class="d-none">'
                              . csrf_field() . method_field('DELETE') .
                              '</form>';
                    }

                    $btn .= '</div></div>';

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
    public function orderHistory(Request $request, OrderManagement $order)
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
        $blockingOrder = $this->sequentialDispatch->findBlockingOrderFor($order);

        $data['dispatchBlocked'] = $blockingOrder !== null;
        $data['blockingOrder']   = $blockingOrder;

        $reopenDispatchId = session('edit_dispatch_id');
        if ($reopenDispatchId) {
            $data['editModalReopenPayload'] = $this->buildEditModalPayload($order, (int) $reopenDispatchId, true);
            if ($data['editModalReopenPayload'] && $request->session()->has('errors')) {
                $data['editModalReopenPayload']['validationErrors'] = $request->session()
                    ->get('errors')
                    ->getBag('default')
                    ->toArray();
            }
        } elseif (request()->filled('edit')) {
            $data['editModalReopenPayload'] = $this->buildEditModalPayload($order, (int) request()->query('edit'), false);
        } else {
            $data['editModalReopenPayload'] = null;
        }

        $data['addDispatchOldInput'] = (! $reopenDispatchId && $request->session()->has('errors'))
            ? [
                'transport_id'   => old('transport_id'),
                'truck_number'   => old('truck_number'),
                'driver_contact' => old('driver_contact'),
            ]
            : null;

        return view('dispatch_management.history', $data);
    }

    /* ------------------------------------------------------------------ */
    /*  STORE                                                             */
    /* ------------------------------------------------------------------ */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id'       => 'required|exists:order_management,id',
            'order_item_id'  => ['required', Rule::exists('order_items', 'id')->whereNull('deleted_at')],
            'product_id'     => 'required|exists:products,id',
            'no_of_bags'     => 'required|integer|min:1',
            'dispatch_date'  => 'required|date',
            'transport_id'   => 'required|exists:users,id',
            'truck_number'   => 'required|string|max:100',
            'driver_contact' => 'required|string|max:20',
            'status'              => 'required|in:0,1,2',
            'partial_paid_amount' => 'nullable|numeric|min:0|required_if:status,2',
        ], [
            'order_item_id.required'  => 'Please select a product.',
            'no_of_bags.required'     => ProductUnit::requiredMessage(),
            'no_of_bags.min'          => ProductUnit::minMessage(),
            // 'no_of_bags.max'          => 'The entered quantity cannot exceed the pending quantity.',
            'dispatch_date.required'   => 'Dispatch date is required.',
            'transport_id.required'   => 'Please select a transporter.',
            'transport_id.exists'     => 'Selected transporter is invalid.',
            'truck_number.required'   => 'Truck number is required.',
            'driver_contact.required' => 'Driver contact is required.',
            'status.required'                 => 'Please select a payment status.',
            'status.in'                       => 'Invalid payment status selected.',
            'partial_paid_amount.required_if' => 'Please enter the paid amount.',
            'partial_paid_amount.numeric'     => 'Please enter a valid paid amount.',
            'partial_paid_amount.min'         => 'Paid amount cannot be negative.',
        ]);

        $validated = $this->normalizeDispatchPayment($validated);

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

        $blockingPrior = $this->sequentialDispatch->findBlockingOrderFor($parentOrder, ['items.dispatches']);

        if ($blockingPrior) {
            return $this->dispatchStoreErrorResponse($request, [
                'order_item_id' => 'Order ' . $blockingPrior->unique_order_id
                    . ' must be fully dispatched before dispatching this order.',
            ]);
        }

        $dispatch = DispatchManagement::create($validated);

        $dispatch->refresh();
        $this->syncOrderPaymentFromDispatches($dispatch);

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

        $this->syncOrderPaymentFromDispatches($dispatch);

        return redirect()
            ->route('dispatch.orderHistory', $orderId)
            ->with('success', 'Dispatch entry deleted successfully.');
    }

    /* ------------------------------------------------------------------ */
    /*  AJAX — payment popup data (Dispatch Pending Payments report)      */
    /* ------------------------------------------------------------------ */
    public function paymentPopupData(DispatchManagement $dispatch, PaymentReceivableService $receivableService)
    {
        SalesScope::authorizeDispatchAccess($dispatch);

        $dispatch->loadMissing([
            'order:id,unique_order_id,brand_id,dealer_id',
            'order.brand:id,name',
            'order.dealer:id,user_id,firm_shop_name',
            'order.dealer.user:id,name',
            'product:id,name,unit',
            'orderItem:id,unit_price',
            'transporter:id,name,phone_no',
        ]);

        $summary = $receivableService->summarizeDispatch($dispatch);

        return response()->json([
            'success' => true,
            'dispatch' => [
                'id'                 => (int) $dispatch->id,
                'no_of_bags'          => (int) $dispatch->no_of_bags,
                'dispatch_date'       => $dispatch->dispatch_date?->format('d M Y') ?? '—',
                'transport_id'        => (int) $dispatch->transport_id,
                'transporter_name'    => $dispatch->transporter?->name ?? '—',
                'truck_number'        => $dispatch->truck_number ?? '—',
                'driver_contact'      => $dispatch->driver_contact ?? '—',
                'status'              => (int) $dispatch->status,
                'partial_paid_amount' => (string) ($dispatch->partial_paid_amount ?? ''),
            ],
            'receivable' => [
                'base_amount'      => $summary['base_amount'],
                'accrued_late_fee' => $summary['accrued_late_fee'],
                'total_receivable' => $summary['total_receivable'],
                'amount_paid'      => $summary['amount_paid'],
                'balance_due'      => $summary['balance_due'],
                'overdue_days'     => $summary['overdue_days'],
                'payment_due_days' => $receivableService->paymentDueDays(),
            ],
            'order' => [
                'id'            => (int) ($dispatch->order?->id ?? 0),
                'unique_order_id' => $dispatch->order?->unique_order_id ?? '—',
                'brand_name'    => $dispatch->order?->brand?->name ?? '—',
                'dealer_name'   => $dispatch->order?->dealer?->user?->name ?? $dispatch->order?->dealer?->firm_shop_name ?? '—',
            ],
            'product' => [
                'name' => $dispatch->product?->name ?? '—',
                'unit' => $dispatch->product?->unit ?? '',
            ],
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  AJAX — update payment status only                                 */
    /* ------------------------------------------------------------------ */
    public function updatePaymentStatus(Request $request, DispatchManagement $dispatch, PaymentReceivableService $receivableService)
    {
        SalesScope::authorizeDispatchAccess($dispatch);

        $validated = $request->validate([
            'status'              => 'required|in:0,1,2',
            'partial_paid_amount' => 'nullable|numeric|min:0|required_if:status,2',
        ], [
            'status.required'                 => 'Please select a payment status.',
            'status.in'                       => 'Invalid payment status selected.',
            'partial_paid_amount.required_if' => 'Please enter the paid amount.',
            'partial_paid_amount.numeric'     => 'Please enter a valid paid amount.',
            'partial_paid_amount.min'         => 'Paid amount cannot be negative.',
        ]);

        $validated = $this->normalizeDispatchPayment($validated);

        if ((int) $validated['status'] === DispatchManagement::STATUS_PARTIAL) {
            $totalReceivable = $receivableService->totalReceivable($dispatch);
            $partialPaid = (float) $validated['partial_paid_amount'];

            if ($partialPaid > $totalReceivable) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors'  => [
                        'partial_paid_amount' => [
                            'Paid amount cannot exceed total receivable of '
                            . PaymentReceivableService::formatMoney($totalReceivable) . '.',
                        ],
                    ],
                ], 422);
            }
        }

        $dispatch->update([
            'status'              => (int) $validated['status'],
            'partial_paid_amount' => $validated['partial_paid_amount'] ?? null,
        ]);

        $dispatch->refresh();
        $this->syncOrderPaymentFromDispatches($dispatch);

        return response()->json([
            'success' => true,
            'message' => 'Dispatch payment status updated successfully.',
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  UPDATE                                                            */
    /* ------------------------------------------------------------------ */
    public function update(Request $request, DispatchManagement $dispatch, PaymentReceivableService $receivableService)
    {
        SalesScope::authorizeDispatchAccess($dispatch);

        $validator = Validator::make($request->all(), [
            'no_of_bags'     => 'required|integer|min:1',
            'dispatch_date'  => 'required|date',
            'transport_id'   => 'required|exists:users,id',
            'truck_number'   => 'required|string|max:100',
            'driver_contact' => 'required|string|max:20',
            'status'              => 'required|in:0,1,2',
            'partial_paid_amount' => 'nullable|numeric|min:0|required_if:status,2',
        ], [
            'no_of_bags.required'     => ProductUnit::requiredMessage(),
            'no_of_bags.min'          => ProductUnit::minMessage(),
            'dispatch_date.required'  => 'Dispatch date is required.',
            'transport_id.required'   => 'Please select a transporter.',
            'transport_id.exists'     => 'Selected transporter is invalid.',
            'truck_number.required'   => 'Truck number is required.',
            'driver_contact.required' => 'Driver contact is required.',
            'status.required'                 => 'Please select a payment status.',
            'status.in'                       => 'Invalid payment status selected.',
            'partial_paid_amount.required_if' => 'Please enter the paid amount.',
            'partial_paid_amount.numeric'     => 'Please enter a valid paid amount.',
            'partial_paid_amount.min'         => 'Paid amount cannot be negative.',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('dispatch.orderHistory', $dispatch->order_id)
                ->withInput()
                ->withErrors($validator)
                ->with('edit_dispatch_id', $dispatch->id);
        }

        $validated = $this->normalizeDispatchPayment($validator->validated());

        if ((int) $validated['status'] === DispatchManagement::STATUS_PARTIAL) {
            $totalReceivable = $receivableService->totalReceivable($dispatch);
            $partialPaid = (float) $validated['partial_paid_amount'];

            if ($partialPaid > $totalReceivable) {
                return redirect()
                    ->route('dispatch.orderHistory', $dispatch->order_id)
                    ->withInput()
                    ->withErrors([
                        'partial_paid_amount' => 'Paid amount cannot exceed total receivable of '
                            . PaymentReceivableService::formatMoney($totalReceivable) . '.',
                    ])
                    ->with('edit_dispatch_id', $dispatch->id);
            }
        }

        /*
         * Over-dispatch guard — only when bag count is being changed.
         * Payment-only updates (same bag qty) must not be blocked.
         */
        $orderItem = $dispatch->orderItem;
        $maxAllowedBags = $orderItem->maxBagsWhenEditing($dispatch);

        if ((int) $validated['no_of_bags'] !== (int) $dispatch->no_of_bags
            && (int) $validated['no_of_bags'] > $maxAllowedBags) {
            return redirect()
                ->route('dispatch.orderHistory', $dispatch->order_id)
                ->withInput()
                ->withErrors(['no_of_bags' => 'The entered quantity cannot exceed the pending quantity.'])
                ->with('edit_dispatch_id', $dispatch->id);
        }

        $dispatch->update([
            'no_of_bags'          => (int) $validated['no_of_bags'],
            'dispatch_date'       => $validated['dispatch_date'],
            'transport_id'        => (int) $validated['transport_id'],
            'truck_number'        => $validated['truck_number'],
            'driver_contact'      => $validated['driver_contact'],
            'status'              => (int) $validated['status'],
            'partial_paid_amount' => $validated['partial_paid_amount'],
        ]);

        $dispatch->refresh();
        $this->syncOrderPaymentFromDispatches($dispatch);

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

        $blockingOrder = $this->sequentialDispatch->findBlockingOrderFor($order);

        $items = $order->items->map(function ($item) {
            $pending = $item->pendingQty();

            return [
                'id'           => $item->id,
                'product_id'   => $item->product_id,
                'product_name' => $item->product?->name ?? '—',
                'product_unit' => $item->product?->unit,
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

    private function normalizeDispatchPayment(array $validated): array
    {
        $validated['partial_paid_amount'] = (int) $validated['status'] === DispatchManagement::STATUS_PARTIAL
            ? $validated['partial_paid_amount']
            : null;

        return $validated;
    }

    private function syncOrderPaymentFromDispatches(DispatchManagement $dispatch): void
    {
        $order = OrderManagement::with(['items.dispatches'])->find($dispatch->order_id);

        if ($order) {
            $order->syncPaymentStatusFromDispatches();
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildEditModalPayload(OrderManagement $order, int $dispatchId, bool $useOldInput): ?array
    {
        $order->loadMissing(['items.dispatches', 'items.product']);

        foreach ($order->items as $item) {
            foreach ($item->dispatches as $dispatch) {
                if ((int) $dispatch->id !== $dispatchId) {
                    continue;
                }

                $effectivePending = $item->maxBagsWhenEditing($dispatch);

                return [
                    'dispatchId'        => (int) $dispatch->id,
                    'transportId'       => (string) ($useOldInput ? old('transport_id', (string) $dispatch->transport_id) : $dispatch->transport_id),
                    'truckNumber'       => (string) ($useOldInput ? old('truck_number', $dispatch->truck_number) : $dispatch->truck_number),
                    'driverContact'     => (string) ($useOldInput ? old('driver_contact', $dispatch->driver_contact) : $dispatch->driver_contact),
                    'status'            => (string) ($useOldInput ? old('status', (string) $dispatch->status) : $dispatch->status),
                    'partialPaidAmount' => (string) ($useOldInput ? old('partial_paid_amount', $dispatch->partial_paid_amount ?? '') : ($dispatch->partial_paid_amount ?? '')),
                    'noOfBags'          => (int) ($useOldInput ? old('no_of_bags', $dispatch->no_of_bags) : $dispatch->no_of_bags),
                    'dispatchDate'      => (string) ($useOldInput ? old('dispatch_date', $dispatch->dispatch_date?->format('Y-m-d')) : ($dispatch->dispatch_date?->format('Y-m-d') ?? '')),
                    'productName'       => (string) ($item->product?->name ?? ''),
                    'effectivePending'  => $effectivePending,
                    'updateUrl'         => route('dispatch.update', $dispatch->id),
                    'productUnit'       => (string) ($item->product?->unit ?? ''),
                ];
            }
        }

        return null;
    }

    /**
     * @param  Builder<DispatchManagement>  $query
     * @return Builder<DispatchManagement>
     */
    private function applyDispatchIndexFilters(Builder $query, Request $request): Builder
    {
        $hasDateFilter = $request->filled('date_from') || $request->filled('date_to');

        if (! $hasDateFilter) {
            $query = FinancialYear::applyDefaultListingFilter(
                $query,
                DispatchManagement::pendingPaymentStatuses(),
                'status',
                'dispatch_date'
            );
        }

        if ($request->filled('date_from')) {
            $query->whereDate('dispatch_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('dispatch_date', '<=', $request->date_to);
        }

        if ($request->filled('dealer_id') && $request->dealer_id !== 'all') {
            SalesScope::authorizeDealerId($request->dealer_id);
            $query->whereHas('order', fn (Builder $q) => $q->where('dealer_id', $request->dealer_id));
        }

        if ($request->filled('order_id') && $request->order_id !== 'all') {
            $query->where('order_id', $request->order_id);
        }

        if ($request->filled('product_id') && $request->product_id !== 'all') {
            $query->where('product_id', $request->product_id);
        }

        return $query;
    }

    /* ------------------------------------------------------------------ */
    /*  RESOURCE STUBS — dispatch UI is modal / order-history based         */
    /* ------------------------------------------------------------------ */
    public function create()
    {
        return redirect()->route('dispatch.index')
            ->with('info', 'Dispatches are created from the order dispatch workflow.');
    }

    public function show(DispatchManagement $dispatch)
    {
        SalesScope::authorizeDispatchAccess($dispatch);

        return redirect()->route('dispatch.orderHistory', $dispatch->order_id);
    }

    public function edit(DispatchManagement $dispatch)
    {
        SalesScope::authorizeDispatchAccess($dispatch);

        return redirect()->route('dispatch.orderHistory', $dispatch->order_id);
    }
}
