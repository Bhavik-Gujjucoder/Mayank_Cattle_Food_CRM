{{-- Dashboard — Add Dispatch modal (order selector + dynamic products) --}}
<div class="modal fade" id="dashboardDispatchModal" tabindex="-1" aria-labelledby="dashboardDispatchModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="dashboardDispatchModalLabel">
                    <i class="ti ti-truck me-2"></i>Soda/Order Dispatch
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form action="{{ route('dispatch.store') }}" method="POST" id="dashboardDispatchForm" novalidate>
                @csrf
                <input type="hidden" name="from_dashboard" value="1">
                <input type="hidden" name="product_id" id="dashboardDispatchProductId">

                <div class="modal-body">
                    <div id="dashboardDispatchBlockedAlert" class="alert alert-warning d-none mb-3"></div>

                    <div class="row">

                        <div class="col-md-12 mb-3">
                            <label class="col-form-label">
                                Order <span class="text-danger">*</span>
                            </label>
                            <select name="order_id" id="dashboardDispatchOrderId" class="form-select">
                                <option value="">-- Select Order --</option>
                            </select>
                            <span class="field-error" id="order_id-error"></span>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="col-form-label">
                                Product <span class="text-danger">*</span>
                            </label>
                            <select name="order_item_id" id="dashboardDispatchOrderItemId" class="form-select" disabled>
                                <option value="">-- Select Order First --</option>
                            </select>
                            <span class="field-error" id="order_item_id-error"></span>
                            <small class="text-info fw-semibold d-block mt-1" id="dashboardDispatchPendingHint"></small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="col-form-label">
                                <span id="dashboardDispatchQtyLabel">{{ \App\Support\ProductUnit::quantityFieldLabel() }}</span> <span class="text-danger">*</span>
                            </label>
                            <input type="number" name="no_of_bags" id="dashboardDispatchNoBags" class="form-control"
                                placeholder="0" min="1" value="{{ old('no_of_bags') }}">
                            <span class="field-error" id="no_of_bags-error"></span>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="col-form-label">
                                Dispatch Date <span class="text-danger">*</span>
                            </label>
                            <div class="icon-form">
                                <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
                                <input type="text" name="dispatch_date" id="dashboardDispatchDate"
                                    class="form-control flatpickr" placeholder="DD-MM-YYYY" autocomplete="off"
                                    value="{{ old('dispatch_date') }}">
                            </div>
                            <span class="field-error" id="dispatch_date-error"></span>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="col-form-label">
                                Transport <span class="text-danger">*</span>
                            </label>
                            <select name="transport_id" id="dashboardDispatchTransport" class="form-select">
                                <option value="">-- Select Transporter --</option>
                                @foreach ($transporters as $transporter)
                                    <option value="{{ $transporter->id }}"
                                        {{ (string) old('transport_id') === (string) $transporter->id ? 'selected' : '' }}>
                                        {{ $transporter->name }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="field-error" id="transport_id-error"></span>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="col-form-label">
                                Truck Number <span class="text-danger">*</span>
                            </label>
                            <select name="truck_number" id="dashboardDispatchTruckNumber" class="form-select" disabled>
                                <option value="">-- Select Transporter First --</option>
                            </select>
                            <span class="field-error" id="truck_number-error"></span>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="col-form-label">
                                Driver Contact <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="driver_contact" id="dashboardDispatchDriverContact"
                                class="form-control" placeholder="Auto-filled from transporter"
                                value="{{ old('driver_contact') }}">
                            <span class="field-error" id="driver_contact-error"></span>
                        </div>

                        @include('dispatch_management.partials.status-field', [
                            'idPrefix' => 'dashboard',
                            'value'    => old('status', '0'),
                        ])

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="dashboardSaveDispatchBtn">
                        <i class="ti ti-check me-1"></i>Save Dispatch
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>
