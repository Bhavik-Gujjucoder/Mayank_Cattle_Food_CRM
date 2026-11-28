<div class="wr-retail-add" style="display:none;">
    <div class="wr-section-label">
        <i class="ti ti-shopping-bag"></i> Retail
    </div>
    <form class="wr-add-retail-form" method="POST" action="{{ route('weekly-report.items.store-retail', $report->id) }}">
        @csrf
        <div class="mb-2">
            <div class="form-check form-check-inline">
                <input class="form-check-input wr-retail-mode" type="radio" name="retail_mode" value="new" checked>
                <label class="form-check-label">New retail order</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input wr-retail-mode" type="radio" name="retail_mode" value="existing">
                <label class="form-check-label">Pending retail order</label>
            </div>
        </div>
        <div class="row g-2">
            <div class="col-12 col-lg-4 wr-retail-existing" style="display:none;">
                <label class="col-form-label">Pending retail order <span class="text-danger">*</span></label>
                <select name="order_item_id" class="form-select form-select-sm wr-retail-order">
                    <option value="">— Select pending retail order —</option>
                </select>
                <span class="text-danger small d-block order_item_id_error"></span>
            </div>
            <div class="col-12 col-md-4 wr-retail-new">
                <label class="col-form-label">Party Name <span class="text-danger">*</span></label>
                <input type="text" name="party_name" class="form-control form-control-sm" maxlength="255">
                <span class="text-danger small d-block party_name_error"></span>
            </div>
            <div class="col-12 col-md-4 wr-retail-new">
                <label class="col-form-label">Brand <span class="text-danger">*</span></label>
                <select name="brand_id" class="form-select form-select-sm wr-retail-brand">
                    <option value="">— Select brand —</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                    @endforeach
                </select>
                <span class="text-danger small d-block brand_id_error"></span>
            </div>
            <div class="col-6 col-md-4 wr-retail-new">
                <label class="col-form-label">Order Date <span class="text-danger">*</span></label>
                <input type="date" name="order_date" class="form-control form-control-sm" value="{{ $report->report_date?->toDateString() }}">
                <span class="text-danger small d-block order_date_error"></span>
            </div>
            <div class="col-12 wr-retail-new">
                <label class="col-form-label">Note</label>
                <textarea name="note" class="form-control form-control-sm" rows="1"></textarea>
            </div>
            <div class="col-12 col-md-4 wr-retail-new">
                <label class="col-form-label">Product <span class="text-danger">*</span></label>
                <select name="product_id" class="form-select form-select-sm wr-retail-product">
                    <option value="">— Select product —</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" data-brand="{{ $product->brand_id }}" data-price="{{ $product->price }}">
                            {{ $product->name }} ({{ $product->unit }})
                        </option>
                    @endforeach
                </select>
                <span class="text-danger small d-block product_id_error"></span>
            </div>
            <div class="col-6 col-md-2 wr-retail-new">
                <label class="col-form-label">Order Qty <span class="text-danger">*</span></label>
                <input type="number" name="order_qty" class="form-control form-control-sm wr-retail-order-qty" min="1" value="1">
                <span class="text-danger small d-block order_qty_error"></span>
            </div>
            <div class="col-6 col-md-2 wr-retail-new">
                <label class="col-form-label">Unit Price <span class="text-danger">*</span></label>
                <input type="number" name="unit_price" class="form-control form-control-sm wr-retail-price" min="0" step="0.01" value="0">
                <span class="text-danger small d-block unit_price_error"></span>
            </div>
            <div class="col-6 col-md-2 wr-retail-new">
                <label class="col-form-label">Payment Status</label>
                <select name="payment_status" class="form-select form-select-sm">
                    <option value="unpaid">Unpaid</option>
                    <option value="partial">Partial</option>
                    <option value="paid">Paid</option>
                </select>
            </div>
            <div class="col-6 col-md-2 wr-retail-new">
                <label class="col-form-label">Payment Type</label>
                <select name="payment_type" class="form-select form-select-sm wr-retail-payment-type">
                    <option value="cash">Cash</option>
                    <option value="credit">Credit</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="col-form-label">Dispatch Qty <span class="text-danger">*</span></label>
                <input type="number" name="quantity" class="form-control form-control-sm wr-retail-dispatch-qty" min="1" value="1" required>
                <small class="text-muted">Can be less than the order quantity.</small>
                <span class="text-danger small d-block quantity_error"></span>
            </div>
            <div class="col-6 col-md-2">
                <label class="col-form-label">Less freight / qty</label>
                <input type="number" name="less_freight_per_qty" class="form-control form-control-sm" min="0" step="0.01">
                <span class="text-danger small d-block less_freight_per_qty_error"></span>
            </div>
            <div class="col-6 col-md-2 wr-retail-late-fee" style="display:none;">
                <label class="col-form-label">Late Fee</label>
                <input type="number" name="accrued_late_fee" class="form-control form-control-sm" min="0" step="0.01" placeholder="0">
                <small class="text-muted">Added to this retail dispatch.</small>
                <span class="text-danger small d-block accrued_late_fee_error"></span>
            </div>
            <div class="col-6 col-md-3">
                @include('weekly_report.partials.transport-field', [
                    'name' => 'transport_id',
                    'selectClass' => 'wr-add-transport',
                    'transporters' => $transporters,
                ])
            </div>
            <div class="col-6 col-md-3">
                @include('weekly_report.partials.truck-field', [
                    'name' => 'truck_number',
                    'selectClass' => 'wr-add-truck',
                    'disabled' => true,
                ])
            </div>
            <div class="col-6 col-md-2">
                <label class="col-form-label">Contact</label>
                <input type="text" name="driver_contact" class="form-control form-control-sm wr-add-contact">
            </div>
            <div class="col-12 col-md-8">
                <label class="col-form-label">Dispatch note</label>
                <textarea name="dispatch_note" class="form-control form-control-sm" rows="1"></textarea>
            </div>
            <div class="col-12 col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="ti ti-plus me-1"></i>Add retail
                </button>
            </div>
        </div>
    </form>
</div>
