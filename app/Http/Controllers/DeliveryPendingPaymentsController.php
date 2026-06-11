<?php

namespace App\Http\Controllers;

use App\Exports\DeliveryPendingPaymentsExport;
use App\Models\BrandManagement;
use App\Services\DeliveryPendingPaymentsReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DeliveryPendingPaymentsController extends Controller
{
    public function __construct(
        protected DeliveryPendingPaymentsReportService $reportService
    ) {}

    public function index(Request $request): View
    {
        $brandFilter = $request->query('brand_id', 'all');

        $brands = BrandManagement::activeForDropdown(['id', 'name']);

        $brandSections = $this->reportService->build($brandFilter);

        $user = auth()->user();
        $canLinkOrder = $user->can('add-dispatch')
            || $user->can('edit-dispatch')
            || $user->can('delete-dispatch');

        return view('delivery_pending_payments.index', [
            'page_title'     => 'Sales — Dispatch Pending Payments',
            'brands'         => $brands,
            'brandFilter'    => $brandFilter,
            'brandSections'  => $brandSections,
            'canLinkOrder'   => $canLinkOrder,
        ]);
    }

    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        $brandFilter = $request->query('brand_id', 'all');

        $brandSections = $this->reportService->build($brandFilter);

        if ($brandSections->isEmpty()) {
            return redirect()
                ->route('delivery-pending-payments.index', $request->only('brand_id'))
                ->with('error', 'No records found to export for the current filters.');
        }

        $filename = 'delivery-pending-payments-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            DeliveryPendingPaymentsExport::fromSections($brandSections),
            $filename
        );
    }
}
