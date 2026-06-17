@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('content')

<div class="delivery-pending-payments-module">

    <div class="card dpp-main-card">
        <div class="card-header">
            <div class="row align-items-center g-2 g-md-3">
                <div class="col-12 col-md-auto me-md-auto">
                    <div class="d-flex align-items-center gap-2">
                        <div class="dispatch-index-icon dpp-header-icon">
                            <i class="ti ti-report-money"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="dispatch-index-eyebrow">Sales Report</div>
                            <div class="dispatch-index-title">Dispatch Pending Payments</div>
                            <p class="text-muted small mb-0 mt-1 d-none d-sm-block">Unpaid dispatch payments after delivery</p>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-5 col-lg-4 dpp-header-filters">
                    <form method="get" action="{{ route('delivery-pending-payments.index') }}" id="dppFilterForm">
                        <select class="form-select select" name="brand_id" id="dppBrandFilter">
                            <option value="all" {{ $brandFilter === 'all' ? 'selected' : '' }}>All Brands</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}"
                                    {{ (string) $brandFilter === (string) $brand->id ? 'selected' : '' }}>
                                    {{ $brand->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>

                <div class="col-12 col-md-auto dpp-header-actions">
                    <a href="{{ route('delivery-pending-payments.export', ['brand_id' => $brandFilter]) }}"
                        class="btn btn-primary dpp-btn-export">
                        <i class="ti ti-file-export me-1"></i>
                        <span class="dpp-btn-label-long">Export Excel</span>
                        <span class="dpp-btn-label-short">Export</span>
                    </a>
                    <button type="button" class="btn btn-outline-secondary dpp-btn-print" onclick="window.print();">
                        <i class="ti ti-printer me-1"></i> Print
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body">
            @if ($brandSections->isEmpty())
                <div class="text-center py-5">
                    <i class="ti ti-circle-check text-success fs-1 mb-3 d-block"></i>
                    <h5 class="mb-2">No pending dispatch payments found</h5>
                    <p class="text-muted mb-0">All dispatch payments are settled, or no dispatches match your filter.</p>
                </div>
            @else
                <div class="dpp-brands-stack">
                    @foreach ($brandSections as $section)
                        @include('delivery_pending_payments.partials.brand-section', [
                            'section' => $section,
                            'canLinkOrder' => $canLinkOrder,
                        ])
                    @endforeach
                </div>
            @endif

            <div class="dpp-footnotes mt-3 pt-3 border-top small text-muted">
                <p class="mb-1 d-md-none dpp-footnote dpp-footnote--mobile">
                    <span class="dpp-footnote-label">Tip:</span>
                    Tap a day chip to see dispatch date. Only unpaid or partial dispatches are listed.
                </p>
                @include('delivery_pending_payments.partials.footnotes-legend', [
                    'modifier' => 'd-none d-md-block d-print-block',
                    'paymentDueDays' => $paymentDueDays ?? 0,
                ])
            </div>
        </div>
    </div>

</div>

@if (!empty($canUpdateDispatchPayment))
    <div class="modal fade" id="dppDispatchPaymentModal" tabindex="-1" aria-labelledby="dppDispatchPaymentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dppDispatchPaymentModalLabel">
                        <i class="ti ti-report-money me-2"></i>Update Dispatch Payment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="dppDispatchPaymentForm">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="col-form-label">Order</label>
                                <input type="text" class="form-control" id="dpp_order_label" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="col-form-label">Dealer</label>
                                <input type="text" class="form-control" id="dpp_dealer_name" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="col-form-label">Brand</label>
                                <input type="text" class="form-control" id="dpp_brand_name" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="col-form-label">Product</label>
                                <input type="text" class="form-control" id="dpp_product_name" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="col-form-label">Dispatched Qty</label>
                                <input type="text" class="form-control" id="dpp_no_of_bags" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="col-form-label">Dispatch Date</label>
                                <input type="text" class="form-control" id="dpp_dispatch_date" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="col-form-label">Truck</label>
                                <input type="text" class="form-control" id="dpp_truck_number" readonly>
                            </div>
                        </div>

                        <hr class="my-3">

                        <div class="row g-3 mb-3" id="dpp_receivable_section">
                            <div class="col-12">
                                <h6 class="mb-2 text-muted">Payment Receivable</h6>
                            </div>
                            <div class="col-md-4">
                                <label class="col-form-label">Base Amount</label>
                                <input type="text" class="form-control" id="dpp_base_amount" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="col-form-label">Accrued Late Fee</label>
                                <input type="text" class="form-control" id="dpp_accrued_late_fee" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="col-form-label">Total Receivable</label>
                                <input type="text" class="form-control fw-medium" id="dpp_total_receivable" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="col-form-label">Overdue Days</label>
                                <input type="text" class="form-control" id="dpp_overdue_days" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="col-form-label">Balance Due</label>
                                <input type="text" class="form-control fw-medium text-danger" id="dpp_balance_due" readonly>
                            </div>
                        </div>

                        <hr class="my-3">

                        <input type="hidden" id="dpp_dispatch_id" value="">

                        <div class="row">
                            @include('dispatch_management.partials.status-field', [
                                'idPrefix' => 'dpp',
                                'name' => 'status',
                                'value' => 0,
                                'partialPaidAmount' => '',
                                'errorId' => 'dpp_status',
                            ])
                        </div>
                        <div class="text-danger small" id="dpp_form_error" style="display:none;"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="dppSavePaymentBtn">
                            <i class="ti ti-check me-1"></i>Update Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@include('delivery_pending_payments.partials.module-responsive')

@endsection

@section('script')
<script>
$(document).ready(function () {
    $('#dppBrandFilter').select2({
        placeholder: 'Filter by brand…',
        width: '100%',
        minimumResultsForSearch: 5,
    });

    $('#dppBrandFilter').on('change', function () {
        $('#dppFilterForm').submit();
    });

    document.querySelectorAll('.delivery-pending-payments-module [data-bs-toggle="tooltip"]')
        .forEach(function (el) {
            new bootstrap.Tooltip(el, { trigger: 'hover focus' });
        });
    
    var dppModalEl = document.getElementById('dppDispatchPaymentModal');
    if (dppModalEl) {
        var dppModal = new bootstrap.Modal(dppModalEl);

    function dppShowError(msg) {
        $('#dpp_form_error').text(msg).show();
    }

    function dppClearError() {
        $('#dpp_form_error').hide().text('');
        $('#dppDispatchPaymentForm .field-error').text('');
    }

    function dppFormatMoney(amount) {
        var n = parseFloat(amount);
        if (isNaN(n)) n = 0;
        return '₹ ' + n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function dppSetReceivable(receivable) {
        receivable = receivable || {};
        $('#dpp_base_amount').val(dppFormatMoney(receivable.base_amount));
        $('#dpp_accrued_late_fee').val(dppFormatMoney(receivable.accrued_late_fee));
        $('#dpp_total_receivable').val(dppFormatMoney(receivable.total_receivable));
        $('#dpp_balance_due').val(dppFormatMoney(receivable.balance_due));
        var overdue = parseInt(receivable.overdue_days, 10) || 0;
        var dueDays = parseInt(receivable.payment_due_days, 10) || 0;
        if (overdue > 0) {
            $('#dpp_overdue_days').val(overdue + ' day(s) past due period (' + dueDays + ' days)');
        } else if (dueDays > 0) {
            $('#dpp_overdue_days').val('Within due period (' + dueDays + ' days)');
        } else {
            $('#dpp_overdue_days').val('—');
        }
    }

    function dppSetStatus(status, partialPaidAmount) {
        var s = String(status);
        $('#dpp_status_unpaid, #dpp_status_paid, #dpp_status_partial').prop('checked', false);
        if (s === '1') $('#dpp_status_paid').prop('checked', true);
        else if (s === '2') $('#dpp_status_partial').prop('checked', true);
        else $('#dpp_status_unpaid').prop('checked', true);

        $('#dpp_partial_paid_amount').val(partialPaidAmount || '');
        $('#dpp_partial_amount_wrap').toggle(s === '2');
        if (s !== '2') $('#dpp_partial_paid_amount').val('');
    }

    $(document).on('click', '.dpp-day-pill[data-dispatch-id], .dpp-day-chip[data-dispatch-id]', function (e) {
        e.preventDefault();
        var dispatchId = $(this).data('dispatch-id');
        if (!dispatchId) return;

        dppClearError();
        $('#dpp_dispatch_id').val(dispatchId);

        $('#dpp_order_label, #dpp_dealer_name, #dpp_brand_name, #dpp_product_name, #dpp_no_of_bags, #dpp_dispatch_date, #dpp_truck_number')
            .val('Loading…');
        dppSetStatus(0, '');
        dppSetReceivable({});

        dppModal && dppModal.show();

        $.get("{{ route('dispatch.paymentPopupData', ':id') }}".replace(':id', dispatchId))
            .done(function (res) {
                if (!res || !res.success) {
                    dppShowError('Failed to load dispatch details.');
                    return;
                }

                $('#dpp_order_label').val(res.order.unique_order_id || '—');
                $('#dpp_dealer_name').val(res.order.dealer_name || '—');
                $('#dpp_brand_name').val(res.order.brand_name || '—');
                $('#dpp_product_name').val((res.product.name || '—') + (res.product.unit ? (' (' + res.product.unit + ')') : ''));
                $('#dpp_no_of_bags').val(res.dispatch.no_of_bags || '—');
                $('#dpp_dispatch_date').val(res.dispatch.dispatch_date || '—');
                $('#dpp_truck_number').val(res.dispatch.truck_number || '—');

                dppSetStatus(res.dispatch.status, res.dispatch.partial_paid_amount);
                dppSetReceivable(res.receivable);
            })
            .fail(function () {
                dppShowError('Failed to load dispatch details.');
            });
    });

    $(document).on('change', '#dppDispatchPaymentForm .dispatch-payment-status-radio', function () {
        var status = $('#dppDispatchPaymentForm input[name="status"]:checked').val();
        $('#dpp_partial_amount_wrap').toggle(String(status) === '2');
        if (String(status) !== '2') {
            $('#dpp_partial_paid_amount').val('');
        }
    });

    $('#dppDispatchPaymentForm').on('submit', function (e) {
        e.preventDefault();

        dppClearError();
        var dispatchId = $('#dpp_dispatch_id').val();
        if (!dispatchId) return;

        var status = $('#dppDispatchPaymentForm input[name="status"]:checked').val();
        var partial = $('#dpp_partial_paid_amount').val();

        if (String(status) === '2' && $.trim(partial) === '') {
            $('#dpp_partial_paid_amount-error').text('Please enter the paid amount.');
            return;
        }

        var $btn = $('#dppSavePaymentBtn');
        $btn.prop('disabled', true);

        $.ajax({
            url: "{{ route('dispatch.updatePaymentStatus', ':id') }}".replace(':id', dispatchId),
            method: 'PATCH',
            data: {
                _token: "{{ csrf_token() }}",
                status: status,
                partial_paid_amount: partial
            },
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                if (res && res.success) {
                    dppModal && dppModal.hide();
                    location.reload();
                    return;
                }
                dppShowError('Failed to update payment status.');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    Object.keys(xhr.responseJSON.errors).forEach(function (field) {
                        var msg = xhr.responseJSON.errors[field][0];
                        if (field === 'status') $('#dpp_status-error').text(msg);
                        if (field === 'partial_paid_amount') $('#dpp_partial_paid_amount-error').text(msg);
                    });
                    dppShowError('Please correct the highlighted fields.');
                } else {
                    dppShowError('Failed to update payment status.');
                }
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    });
    }
});
</script>
@endsection
