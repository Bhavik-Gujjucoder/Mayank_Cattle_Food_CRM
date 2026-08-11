@php
    use App\Support\SalesScope;
    $dealers = $dealers ?? collect();
    $showDealerFilter = SalesScope::showDealerFilter();
@endphp
<div class="wr-add-panel">
    <div class="wr-section-label">
        <i class="ti ti-square-rounded-plus"></i> Add order line
    </div>
    <form class="wr-add-item-form"
        method="POST"
        action="{{ route('weekly-report.items.store', $report->id) }}"
        data-block-id="{{ $blockId }}">
        @csrf
        <div class="row g-2 wr-add-form-grid">
            @if ($showDealerFilter)
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="col-form-label">Dealer <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm wr-add-dealer" required>
                        <option value="">— Select dealer —</option>
                        @foreach ($dealers as $dealer)
                            <option value="{{ $dealer->id }}">
                                {{ $dealer->user?->name ?? $dealer->firm_shop_name ?? '—' }}
                            </option>
                        @endforeach
                    </select>
                    <span class="text-danger small d-block dealer_id_error"></span>
                </div>
            @endif
            <div class="col-12 {{ $showDealerFilter ? 'col-md-6 col-lg-3' : 'col-lg-4' }}">
                <label class="col-form-label">Pending order line <span class="text-danger">*</span></label>
                <select name="order_item_id" class="form-select form-select-sm wr-add-order-item" required
                    @if ($showDealerFilter) disabled @endif>
                    <option value="">
                        {{ $showDealerFilter ? '— Select dealer first —' : '— Select a pending order —' }}
                    </option>
                </select>
                <small class="text-muted wr-add-pending-hint"></small>
                <span class="text-danger small d-block order_item_id_error"></span>
            </div>
            <div class="col-6 col-md-4 col-lg-1 wr-add-qty-col">
                <label class="col-form-label">Qty <span class="text-danger">*</span></label>
                <input type="number" name="quantity" class="form-control form-control-sm wr-add-quantity" min="1" required>
                <span class="text-danger small d-block quantity_error"></span>
            </div>
            <div class="col-6 col-md-4 col-lg-1 wr-add-entries-col">
                <label class="col-form-label">No. of Entries <span class="text-danger">*</span></label>
                <input type="number" name="no_of_entries" class="form-control form-control-sm wr-add-entries"
                    min="1" max="100" value="1" required>
                <span class="text-danger small d-block no_of_entries_error"></span>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                @include('weekly_report.partials.transport-field', [
                    'name' => 'transport_id',
                    'selectClass' => 'wr-add-transport',
                    'transporters' => $transporters,
                ])
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                @include('weekly_report.partials.truck-field', [
                    'name' => 'truck_number',
                    'selectClass' => 'wr-add-truck',
                    'disabled' => true,
                ])
            </div>
            <div class="col-6 col-md-4 col-lg-{{ $showDealerFilter ? '1' : '2' }}">
                <label class="col-form-label">Contact</label>
                <input type="text" name="driver_contact" class="form-control form-control-sm wr-add-contact">
            </div>
            <div class="col-12 col-lg-10">
                <label class="col-form-label">Note</label>
                <textarea name="note" class="form-control form-control-sm wr-add-note" rows="1"
                    placeholder="Optional note"></textarea>
            </div>
            <div class="col-12 col-lg-2 d-flex align-items-end wr-add-submit-col">
                <button type="submit" class="btn btn-primary btn-sm wr-add-submit-btn">
                    <i class="ti ti-plus me-1"></i>Add to report
                </button>
            </div>
        </div>
    </form>
</div>
