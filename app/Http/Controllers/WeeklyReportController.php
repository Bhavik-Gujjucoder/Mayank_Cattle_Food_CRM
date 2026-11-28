<?php

namespace App\Http\Controllers;

use App\Exports\WeeklyReportExport;
use App\Models\BrandManagement;
use App\Models\OrderManagement;
use App\Models\Product;
use App\Models\User;
use App\Models\WeeklyReport;
use App\Models\WeeklyReportItem;
use App\Services\WeeklyReportService;
use App\Support\ProductUnit;
use App\Support\SalesScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\DataTables;

class WeeklyReportController extends Controller
{
    public function __construct(
        private WeeklyReportService $weeklyReports,
    ) {
        $this->middleware('permission:view-weekly-report')->only([
            'index', 'show', 'searchPendingItems', 'export', 'print',
        ]);
        $this->middleware('permission:add-weekly-report')->only([
            'create', 'store',
        ]);
        $this->middleware('permission:edit-weekly-report')->only([
            'updateAlreadyProduced', 'storeItem', 'storeRetailItem', 'updateItem', 'reorderItems', 'confirmItem',
        ]);
        $this->middleware('permission:delete-weekly-report')->only([
            'destroy', 'destroyItem',
        ]);
    }

    public function index(Request $request)
    {
        $data['page_title'] = 'Daily Dispatch';

        if ($request->ajax()) {
            $canDelete = auth()->user()->can('delete-weekly-report');

            $query = WeeklyReport::query()->withCount([
                'items',
                'items as confirmed_items_count' => fn ($q) => $q->where('status', WeeklyReportItem::STATUS_CONFIRMED),
            ])->with(['items.product:id,unit']);

            if ($request->filled('date_from')) {
                $query->whereDate('report_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('report_date', '<=', $request->date_to);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('report_date', fn (WeeklyReport $row) => $row->report_date?->format('d M Y')
                    . ' — ' . strtoupper($row->report_date?->format('l') ?? ''))
                ->addColumn('items_summary', function (WeeklyReport $row) {
                    return (int) $row->items_count . ' row(s), '
                        . (int) $row->confirmed_items_count . ' confirmed';
                })
                ->addColumn('total_qty', fn (WeeklyReport $row) => number_format($row->totalQuantityInBags(), 2))
                ->addColumn('already_produced_col', fn (WeeklyReport $row) => number_format((float) $row->already_produced, 2))
                ->addColumn('difference', fn (WeeklyReport $row) => number_format($row->differenceInBags(), 2))
                ->addColumn('hours', fn (WeeklyReport $row) => number_format($row->productionHours(), 2))
                ->addColumn('action', function (WeeklyReport $row) use ($canDelete) {
                    $view = '<a href="' . route('weekly-report.index', ['date' => $row->report_date->toDateString()]) . '" class="dropdown-item">
                               <i class="ti ti-eye text-info"></i> Open
                           </a>';

                    $delete = '';
                    if ($canDelete && (int) $row->confirmed_items_count === 0) {
                        $delete = '<a href="javascript:void(0)" class="dropdown-item delete-weekly-report" data-id="' . $row->id . '">
                               <i class="ti ti-trash text-danger"></i> Delete
                           </a>
                           <form action="' . route('weekly-report.destroy', $row->id) . '" method="POST"
                                 class="d-none" id="delete-weekly-report-form-' . $row->id . '">'
                            . csrf_field() . method_field('DELETE') .
                          '</form>';
                    }

                    return '<div class="dropdown table-action">
                                <a href="#" class="action-icon" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa fa-ellipsis-v"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">'
                                    . $view . $delete .
                               '</div>
                            </div>';
                })
                ->rawColumns(['action'])
                ->orderColumn('report_date', 'report_date $1')
                ->filterColumn('report_date', function ($query, $keyword) {
                    $query->whereRaw("DATE_FORMAT(report_date, '%d/%m/%Y') like ?", ["%{$keyword}%"]);
                })
                ->make(true);
        }

        try {
            $filters = $this->weeklyReports->resolveWorkspaceFilters(
                $request->input('date'),
                $request->input('date_from'),
                $request->input('date_to'),
                $request->input('order_type'),
            );
        } catch (ValidationException $e) {
            return redirect()
                ->route('weekly-report.index', ['date' => now()->toDateString()])
                ->with('error', collect($e->errors())->flatten()->first());
        }

        try {
            $autoCreate = auth()->user()->can('add-weekly-report');
            $days = $this->weeklyReports->workspaceDays($filters, $autoCreate);
        } catch (ValidationException $e) {
            return redirect()
                ->route('weekly-report.index', ['date' => now()->toDateString()])
                ->with('error', collect($e->errors())->flatten()->first());
        }

        $focusDate = $filters['mode'] === 'single'
            ? Carbon::parse($filters['date'])
            : null;

        return view('weekly_report.index', array_merge($data, [
            'filters'      => $filters,
            'days'         => $days,
            'focusDate'    => $focusDate,
            'transporters' => $this->transporters(),
            'dealers'      => SalesScope::filterableDealers(),
            'brands'       => BrandManagement::activeForDropdown(),
            'products'     => Product::query()->where('status', 1)->orderBy('name')->get(['id', 'name', 'unit', 'brand_id', 'price']),
            'allowRetail'  => SalesScope::hasGlobalAccess(),
            'prevDate'     => $focusDate?->copy()->subDay()->toDateString(),
            'nextDate'     => $focusDate?->copy()->addDay()->toDateString(),
            'exportQuery'  => $this->exportQueryFromFilters($filters, false),
        ]));
    }

    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        try {
            ['filters' => $filters, 'days' => $days] = $this->workspaceContext(
                $request,
                WeeklyReportService::MAX_WORKSPACE_DAYS,
            );
        } catch (ValidationException $e) {
            return redirect()
                ->route('weekly-report.index', ['date' => now()->toDateString()])
                ->with('error', collect($e->errors())->flatten()->first());
        }

        if ($days === []) {
            return redirect()
                ->route('weekly-report.index', $this->exportQueryFromFilters($filters, false))
                ->with('error', 'No records found to export for the current filters.');
        }

        $filename = $this->exportFilename($filters);

        return Excel::download(new WeeklyReportExport($days, $filters), $filename);
    }

    public function print(Request $request): View|RedirectResponse
    {
        try {
            ['filters' => $filters, 'days' => $days] = $this->workspaceContext(
                $request,
                WeeklyReportService::MAX_WORKSPACE_DAYS,
            );
        } catch (ValidationException $e) {
            return redirect()
                ->route('weekly-report.index', ['date' => now()->toDateString()])
                ->with('error', collect($e->errors())->flatten()->first());
        }

        return view('weekly_report.print', [
            'page_title' => 'Daily Dispatch — Print',
            'filters'    => $filters,
            'days'       => $days,
            'autoPrint'  => $request->boolean('auto_print'),
        ]);
    }

    public function create()
    {
        return redirect()->route('weekly-report.index', ['date' => now()->toDateString()]);
    }

    public function store(Request $request)
    {
        $mode = $request->input('mode', 'day');

        if ($mode === 'week') {
            $validated = $request->validate([
                'week_start' => 'required|date',
            ], [
                'week_start.required' => 'Please select the week start (Thursday).',
            ]);

            $result = $this->weeklyReports->createWeekReports($validated['week_start']);
            $first = $result['created']->sortBy('report_date')->first();
            $msg = $result['created']->count() . ' day report(s) created.';
            if ($result['skipped']) {
                $msg .= ' Skipped existing: ' . implode(', ', $result['skipped']) . '.';
            }

            return redirect()
                ->route('weekly-report.index', [
                    'date_from' => $first->report_date->toDateString(),
                    'date_to'   => $result['created']->sortByDesc('report_date')->first()->report_date->toDateString(),
                ])
                ->with('success', $msg);
        }

        $validated = $request->validate([
            'report_date' => 'required|date',
        ], [
            'report_date.required' => 'Please select a report date.',
        ]);

        $report = $this->weeklyReports->findOrCreateDayReport($validated['report_date']);

        return redirect()
            ->route('weekly-report.index', ['date' => $report->report_date->toDateString()])
            ->with('success', 'Daily Dispatch ready for ' . $report->report_date->format('d M Y') . '.');
    }

    public function show(WeeklyReport $weeklyReport)
    {
        return redirect()->route('weekly-report.index', [
            'date' => $weeklyReport->report_date->toDateString(),
        ]);
    }

    public function destroy(WeeklyReport $weeklyReport)
    {
        try {
            $this->weeklyReports->deleteReport($weeklyReport);
        } catch (ValidationException $e) {
            return redirect()
                ->route('weekly-report.index')
                ->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()
            ->route('weekly-report.index')
            ->with('success', 'Daily Dispatch deleted successfully.');
    }

    public function updateAlreadyProduced(Request $request, WeeklyReport $weeklyReport)
    {
        $validated = $request->validate([
            'already_produced' => 'required|numeric|min:0',
            'production_hours' => 'nullable|numeric',
            'bags_per_hour'    => 'nullable|numeric|min:0.01',
        ]);

        try {
            $this->weeklyReports->updateFooter($weeklyReport, $validated);
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors'  => $e->errors(),
                ], 422);
            }
            throw $e;
        }

        if ($request->expectsJson()) {
            $weeklyReport->load('items.product:id,unit');

            return response()->json([
                'success'       => true,
                'total'         => $weeklyReport->totalQuantityInBags(),
                'difference'    => $weeklyReport->differenceInBags(),
                'hours'         => $weeklyReport->productionHours(),
                'bags_per_hour' => $weeklyReport->bagsPerHour(),
            ]);
        }

        return redirect()
            ->route('weekly-report.index', ['date' => $weeklyReport->report_date->toDateString()])
            ->with('success', 'Footer values updated.');
    }

    public function searchPendingItems(Request $request)
    {
        $term = $request->input('q', $request->input('term'));
        $orderType = $request->input('order_type') === OrderManagement::TYPE_RETAIL
            ? OrderManagement::TYPE_RETAIL
            : OrderManagement::TYPE_SODA;

        if ($orderType === OrderManagement::TYPE_RETAIL) {
            if (! SalesScope::hasGlobalAccess()) {
                abort(403, 'Retail orders are available to admin and staff only.');
            }

            return response()->json([
                'results' => $this->weeklyReports->searchPendingOrderItems($term, 50, null, OrderManagement::TYPE_RETAIL),
            ]);
        }

        $dealerId = $request->filled('dealer_id') ? (int) $request->input('dealer_id') : null;

        if (SalesScope::showDealerFilter()) {
            if (! $dealerId) {
                return response()->json(['results' => []]);
            }

            if (! SalesScope::userCanFilterByDealer($dealerId)) {
                return response()->json(['results' => []], 403);
            }
        } else {
            $dealerId = null;
        }

        return response()->json([
            'results' => $this->weeklyReports->searchPendingOrderItems($term, 50, $dealerId),
        ]);
    }

    public function storeItem(Request $request, WeeklyReport $weeklyReport)
    {
        $validated = $request->validate([
            'order_item_id'  => ['required', Rule::exists('order_items', 'id')->whereNull('deleted_at')],
            'quantity'       => 'required|integer|min:1',
            'less_freight_per_qty' => 'nullable|numeric|min:0',
            'accrued_late_fee' => 'nullable|numeric|min:0',
            'late_fee_dispatch_id' => 'nullable|integer|exists:dispatch_management,id',
            'no_of_entries'  => 'nullable|integer|min:1|max:100',
            'transport_id'   => 'nullable|exists:users,id',
            'truck_number'   => 'nullable|string|max:100',
            'driver_contact' => 'nullable|string|max:20',
            'note'           => 'nullable|string|max:2000',
        ], [
            'order_item_id.required' => 'Please select a pending order line.',
            'quantity.required'      => ProductUnit::requiredMessage(),
            'quantity.min'           => ProductUnit::minMessage(),
            'less_freight_per_qty.numeric' => 'Please enter a valid less freight amount.',
            'less_freight_per_qty.min'     => 'Less freight cannot be negative.',
            'accrued_late_fee.numeric' => 'Please enter a valid late fee.',
            'accrued_late_fee.min'     => 'Late fee cannot be negative.',
            'late_fee_dispatch_id.exists' => 'Select an unpaid or partial dispatch for this dealer.',
            'no_of_entries.min'      => 'No. of Entries must be at least 1.',
            'no_of_entries.max'      => 'No. of Entries cannot exceed 100.',
        ]);

        $validated['no_of_entries'] = (int) ($validated['no_of_entries'] ?? 1);

        try {
            $items = $this->weeklyReports->addItem($weeklyReport, $validated);
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors'  => $e->errors(),
                ], 422);
            }
            throw $e;
        }

        $count = $items->count();
        $message = $count === 1 ? 'Row added.' : $count . ' rows added.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'item_id' => $items->first()?->id,
                'count'   => $count,
            ]);
        }

        return redirect()
            ->route('weekly-report.index', ['date' => $weeklyReport->report_date->toDateString()])
            ->with('success', $count === 1 ? 'Row added to report.' : $count . ' rows added to report.');
    }

    public function storeRetailItem(Request $request, WeeklyReport $weeklyReport)
    {
        if (! SalesScope::hasGlobalAccess()) {
            abort(403, 'Retail orders are available to admin and staff only.');
        }

        $mode = $request->input('retail_mode') === 'existing' ? 'existing' : 'new';
        $rules = [
            'quantity' => 'required|integer|min:1',
            'less_freight_per_qty' => 'nullable|numeric|min:0',
            'accrued_late_fee' => 'nullable|numeric|min:0',
            'late_fee_dispatch_id' => 'nullable|integer|exists:dispatch_management,id',
            'transport_id' => 'nullable|exists:users,id',
            'truck_number' => 'nullable|string|max:100',
            'driver_contact' => 'nullable|string|max:20',
            'dispatch_note' => 'nullable|string|max:2000',
        ];

        if ($mode === 'existing') {
            $rules['order_item_id'] = ['required', Rule::exists('order_items', 'id')->whereNull('deleted_at')];
        } else {
            $rules['party_name'] = 'required|string|max:255';
            $rules['brand_id'] = 'required|exists:brand_management,id';
            $rules['order_date'] = 'required|date';
            $rules['note'] = 'nullable|string|max:2000';
            $rules['product_id'] = 'required|exists:products,id';
            $rules['order_qty'] = 'required|integer|min:1';
            $rules['unit_price'] = 'required|numeric|min:0';
            $rules['payment_status'] = 'required|in:unpaid,paid,partial';
            $rules['payment_type'] = 'required|in:cash,credit';
            $rules['partial_paid_amount'] = 'nullable|numeric|min:0|required_if:payment_status,partial';
        }

        $validated = $request->validate($rules, [
            'order_item_id.required' => 'Select a pending retail order.',
            'party_name.required' => 'Party name is required.',
            'order_qty.min' => 'Order quantity must be at least 1.',
            'quantity.min' => 'Dispatch quantity must be at least 1.',
        ]);

        if ($mode === 'new' && (int) $validated['quantity'] > (int) $validated['order_qty']) {
            throw ValidationException::withMessages([
                'quantity' => 'Dispatch quantity cannot be more than the order quantity.',
            ]);
        }

        $validated['retail_mode'] = $mode;
        if ($mode === 'new' && ($validated['payment_type'] ?? '') !== 'credit') {
            $validated['accrued_late_fee'] = null;
            $validated['late_fee_dispatch_id'] = null;
        }

        try {
            $this->weeklyReports->addRetailLine($weeklyReport, $validated);
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $e->errors(),
                ], 422);
            }
            throw $e;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Retail order added to Daily Dispatch.',
            ]);
        }

        return redirect()
            ->route('weekly-report.index', ['date' => $weeklyReport->report_date->toDateString()])
            ->with('success', 'Retail order added to Daily Dispatch.');
    }

    public function updateItem(Request $request, WeeklyReport $weeklyReport, WeeklyReportItem $weeklyReportItem)
    {
        $this->assertItemBelongsToReport($weeklyReport, $weeklyReportItem);

        $rules = [
            'sort_order' => 'nullable|integer|min:0',
        ];

        if (! $weeklyReportItem->isLocked()) {
            $rules = array_merge($rules, [
                'quantity'       => 'required|integer|min:1',
                'less_freight_per_qty' => 'nullable|numeric|min:0',
                'accrued_late_fee' => 'nullable|numeric|min:0',
                'late_fee_dispatch_id' => 'nullable|integer|exists:dispatch_management,id',
                'transport_id'   => 'nullable|exists:users,id',
                'truck_number'   => 'nullable|string|max:100',
                'driver_contact' => 'nullable|string|max:20',
                'note'           => 'nullable|string|max:2000',
            ]);
        }

        $validated = $request->validate($rules);

        try {
            $this->weeklyReports->updateItem($weeklyReportItem, $validated);
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors'  => $e->errors(),
                ], 422);
            }
            throw $e;
        }

        if ($request->expectsJson()) {
            $weeklyReport->load('items.product:id,unit');

            return response()->json([
                'success'    => true,
                'message'    => 'Row updated.',
                'total'      => $weeklyReport->totalQuantityInBags(),
                'difference' => $weeklyReport->differenceInBags(),
                'hours'      => $weeklyReport->productionHours(),
            ]);
        }

        return redirect()
            ->route('weekly-report.index', ['date' => $weeklyReport->report_date->toDateString()])
            ->with('success', 'Row updated.');
    }

    public function reorderItems(Request $request, WeeklyReport $weeklyReport)
    {
        $validated = $request->validate([
            'orders'              => 'required|array',
            'orders.*.id'         => 'required|integer|exists:weekly_report_items,id',
            'orders.*.sort_order' => 'required|integer|min:0',
        ]);

        $this->weeklyReports->reorderItems($weeklyReport, $validated['orders']);

        return response()->json(['success' => true]);
    }

    public function destroyItem(WeeklyReport $weeklyReport, WeeklyReportItem $weeklyReportItem)
    {
        $this->assertItemBelongsToReport($weeklyReport, $weeklyReportItem);

        try {
            $this->weeklyReports->deleteItem($weeklyReportItem);
        } catch (ValidationException $e) {
            return redirect()
                ->route('weekly-report.index', ['date' => $weeklyReport->report_date->toDateString()])
                ->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()
            ->route('weekly-report.index', ['date' => $weeklyReport->report_date->toDateString()])
            ->with('success', 'Row removed.');
    }

    public function confirmItem(Request $request, WeeklyReport $weeklyReport, WeeklyReportItem $weeklyReportItem)
    {
        $this->assertItemBelongsToReport($weeklyReport, $weeklyReportItem);

        if (! auth()->user()->can('add-dispatch')) {
            abort(403, 'You need add-dispatch permission to confirm a row.');
        }

        $rules = [
            'status'              => 'required|in:0,1,2',
            'partial_paid_amount' => 'nullable|numeric|min:0|required_if:status,2',
        ];

        $validated = $request->validate($rules, [
            'status.required'                 => 'Please select a payment status.',
            'partial_paid_amount.required_if' => 'Please enter the paid amount.',
            'accrued_late_fee.numeric'        => 'Please enter a valid late fee.',
            'accrued_late_fee.min'            => 'Late fee cannot be negative.',
        ]);

        $validated['partial_paid_amount'] = (int) $validated['status'] === \App\Models\DispatchManagement::STATUS_PARTIAL
            ? $validated['partial_paid_amount']
            : null;

        try {
            $dispatch = $this->weeklyReports->confirmItem($weeklyReportItem, $validated);
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors'  => $e->errors(),
                ], 422);
            }

            return redirect()
                ->route('weekly-report.index', ['date' => $weeklyReport->report_date->toDateString()])
                ->withInput()
                ->withErrors($e->errors())
                ->with('error', collect($e->errors())->flatten()->first());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success'     => true,
                'message'     => 'Dispatch created and row confirmed.',
                'dispatch_id' => $dispatch->id,
            ]);
        }

        return redirect()
            ->route('weekly-report.index', ['date' => $weeklyReport->report_date->toDateString()])
            ->with('success', 'Dispatch entry created. Row is now locked.');
    }

    private function assertItemBelongsToReport(WeeklyReport $report, WeeklyReportItem $item): void
    {
        if ((int) $item->weekly_report_id !== (int) $report->id) {
            abort(404);
        }
    }

    /**
     * @return array{filters: array, days: list<array{date: Carbon, report: WeeklyReport|null}>}
     */
    private function workspaceContext(Request $request, int $maxDays): array
    {
        $filters = $this->weeklyReports->resolveWorkspaceFilters(
            $request->input('date') ?? $request->input('wr_date'),
            $request->input('date_from') ?? $request->input('wr_date_from'),
            $request->input('date_to') ?? $request->input('wr_date_to'),
            $request->input('order_type'),
        );

        $days = $this->weeklyReports->workspaceDays($filters, false, $maxDays);

        return compact('filters', 'days');
    }

    /**
     * @param  array{mode: string, date: string|null, date_from: string|null, date_to: string|null, order_type?: string|null}  $filters
     * @return array<string, string>
     */
    private function exportQueryFromFilters(array $filters, bool $dashboard): array
    {
        $orderType = $filters['order_type'] ?? null;

        if (($filters['mode'] ?? 'single') === 'range') {
            return array_filter([
                $dashboard ? 'wr_date_from' : 'date_from' => $filters['date_from'] ?? null,
                $dashboard ? 'wr_date_to' : 'date_to'     => $filters['date_to'] ?? null,
                'order_type' => $orderType,
            ]);
        }

        return array_filter([
            $dashboard ? 'wr_date' : 'date' => $filters['date'] ?? null,
            'order_type' => $orderType,
        ]);
    }

    /**
     * @param  array{mode: string, date: string|null, date_from: string|null, date_to: string|null}  $filters
     */
    private function exportFilename(array $filters): string
    {
        if (($filters['mode'] ?? 'single') === 'range') {
            return 'daily-dispatch-'
                . Carbon::parse($filters['date_from'])->format('Y-m-d')
                . '-to-'
                . Carbon::parse($filters['date_to'])->format('Y-m-d')
                . '.xlsx';
        }

        return 'daily-dispatch-' . Carbon::parse($filters['date'] ?? now())->format('Y-m-d') . '.xlsx';
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, User> */
    private function transporters()
    {
        return User::whereHas('roles', fn ($q) => $q->where('name', 'transporter'))
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'phone_no']);
    }
}
