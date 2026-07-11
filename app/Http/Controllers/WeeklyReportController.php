<?php

namespace App\Http\Controllers;

use App\Models\DispatchManagement;
use App\Models\User;
use App\Models\WeeklyReport;
use App\Models\WeeklyReportItem;
use App\Services\WeeklyReportService;
use App\Support\ProductUnit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\DataTables;

class WeeklyReportController extends Controller
{
    public function __construct(
        private WeeklyReportService $weeklyReports,
    ) {
        $this->middleware('permission:view-weekly-report')->only([
            'index', 'show', 'searchPendingItems',
        ]);
        $this->middleware('permission:add-weekly-report')->only([
            'create', 'store',
        ]);
        $this->middleware('permission:edit-weekly-report')->only([
            'updateAlreadyProduced', 'storeItem', 'updateItem', 'reorderItems', 'confirmItem',
        ]);
        $this->middleware('permission:delete-weekly-report')->only([
            'destroy', 'destroyItem',
        ]);
    }

    public function index(Request $request)
    {
        $data['page_title'] = 'Weekly Report';

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
                ->editColumn('report_date', fn (WeeklyReport $row) => $row->report_date?->format('d/m/Y')
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
                    $view = '<a href="' . route('weekly-report.show', $row->id) . '" class="dropdown-item">
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

        return view('weekly_report.index', $data);
    }

    public function create()
    {
        return view('weekly_report.create', [
            'page_title' => 'Generate Weekly Report',
        ]);
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
                ->route('weekly-report.show', $first->id)
                ->with('success', $msg);
        }

        $validated = $request->validate([
            'report_date' => 'required|date',
        ], [
            'report_date.required' => 'Please select a report date.',
        ]);

        $report = $this->weeklyReports->createDayReport($validated['report_date']);

        return redirect()
            ->route('weekly-report.show', $report->id)
            ->with('success', 'Weekly report created for ' . $report->report_date->format('d/m/Y') . '.');
    }

    public function show(WeeklyReport $weeklyReport)
    {
        $weeklyReport->load([
            'items.product:id,name,unit',
            'items.order:id,unique_order_id,dealer_id',
            'items.order.dealer:id,user_id,firm_shop_name,city_id',
            'items.order.dealer.user:id,name',
            'items.order.dealer.city:id,city_name',
            'items.transporter:id,name,phone_no',
            'items.dispatch:id',
        ]);

        $transporters = User::whereHas('roles', fn ($q) => $q->where('name', 'transporter'))
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'phone_no']);

        return view('weekly_report.show', [
            'page_title'   => 'Weekly Report — ' . $weeklyReport->report_date->format('d/m/Y'),
            'report'       => $weeklyReport,
            'transporters' => $transporters,
            'bagsPerHour'  => WeeklyReport::BAGS_PER_HOUR,
            'totalBags'    => $weeklyReport->totalQuantityInBags(),
            'difference'   => $weeklyReport->differenceInBags(),
            'hours'        => $weeklyReport->productionHours(),
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
            ->with('success', 'Weekly report deleted successfully.');
    }

    public function updateAlreadyProduced(Request $request, WeeklyReport $weeklyReport)
    {
        $validated = $request->validate([
            'already_produced' => 'required|numeric|min:0',
            'production_hours' => 'nullable|numeric|min:0',
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
                'success'    => true,
                'total'      => $weeklyReport->totalQuantityInBags(),
                'difference' => $weeklyReport->differenceInBags(),
                'hours'      => $weeklyReport->productionHours(),
            ]);
        }

        return redirect()
            ->route('weekly-report.show', $weeklyReport->id)
            ->with('success', 'Footer values updated.');
    }

    public function searchPendingItems(Request $request)
    {
        $term = $request->input('q', $request->input('term'));

        return response()->json([
            'results' => $this->weeklyReports->searchPendingOrderItems($term),
        ]);
    }

    public function storeItem(Request $request, WeeklyReport $weeklyReport)
    {
        $validated = $request->validate([
            'order_item_id'  => ['required', Rule::exists('order_items', 'id')->whereNull('deleted_at')],
            'quantity'       => 'required|integer|min:1',
            'transport_id'   => 'nullable|exists:users,id',
            'truck_number'   => 'nullable|string|max:100',
            'driver_contact' => 'nullable|string|max:20',
            'note'           => 'nullable|string|max:2000',
        ], [
            'order_item_id.required' => 'Please select a pending order line.',
            'quantity.required'      => ProductUnit::requiredMessage(),
            'quantity.min'           => ProductUnit::minMessage(),
        ]);

        try {
            $item = $this->weeklyReports->addItem($weeklyReport, $validated);
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
            return response()->json([
                'success' => true,
                'message' => 'Row added.',
                'item_id' => $item->id,
            ]);
        }

        return redirect()
            ->route('weekly-report.show', $weeklyReport->id)
            ->with('success', 'Row added to report.');
    }

    public function updateItem(Request $request, WeeklyReport $weeklyReport, WeeklyReportItem $weeklyReportItem)
    {
        $this->assertItemBelongsToReport($weeklyReport, $weeklyReportItem);

        $validated = $request->validate([
            'quantity'       => 'required|integer|min:1',
            'transport_id'   => 'nullable|exists:users,id',
            'truck_number'   => 'nullable|string|max:100',
            'driver_contact' => 'nullable|string|max:20',
            'note'           => 'nullable|string|max:2000',
            'sort_order'     => 'nullable|integer|min:0',
        ]);

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
            ->route('weekly-report.show', $weeklyReport->id)
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
                ->route('weekly-report.show', $weeklyReport->id)
                ->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()
            ->route('weekly-report.show', $weeklyReport->id)
            ->with('success', 'Row removed.');
    }

    public function confirmItem(Request $request, WeeklyReport $weeklyReport, WeeklyReportItem $weeklyReportItem)
    {
        $this->assertItemBelongsToReport($weeklyReport, $weeklyReportItem);

        if (! auth()->user()->can('add-dispatch')) {
            abort(403, 'You need add-dispatch permission to confirm a row.');
        }

        $validated = $request->validate([
            'status'              => 'required|in:0,1,2',
            'partial_paid_amount' => 'nullable|numeric|min:0|required_if:status,2',
        ], [
            'status.required'                 => 'Please select a payment status.',
            'partial_paid_amount.required_if' => 'Please enter the paid amount.',
        ]);

        $validated['partial_paid_amount'] = (int) $validated['status'] === DispatchManagement::STATUS_PARTIAL
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
                ->route('weekly-report.show', $weeklyReport->id)
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
            ->route('weekly-report.show', $weeklyReport->id)
            ->with('success', 'Dispatch entry created. Row is now locked.');
    }

    private function assertItemBelongsToReport(WeeklyReport $report, WeeklyReportItem $item): void
    {
        if ((int) $item->weekly_report_id !== (int) $report->id) {
            abort(404);
        }
    }
}
