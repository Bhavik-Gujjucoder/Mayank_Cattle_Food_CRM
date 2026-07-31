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
            <div class="col-12 col-lg-5">
                <label class="col-form-label">Pending order line <span class="text-danger">*</span></label>
                <select name="order_item_id" class="form-select form-select-sm wr-add-order-item" required>
                    <option value="">— Select a pending order —</option>
                </select>
                <small class="text-muted wr-add-pending-hint"></small>
                <span class="text-danger small d-block order_item_id_error"></span>
            </div>
            <div class="col-6 col-md-4 col-lg-1 wr-add-qty-col">
                <label class="col-form-label">Qty <span class="text-danger">*</span></label>
                <input type="number" name="quantity" class="form-control form-control-sm wr-add-quantity" min="1" required>
                <span class="text-danger small d-block quantity_error"></span>
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
            <div class="col-6 col-md-4 col-lg-2">
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
