<form id="quickTruckForm" action="{{ route('truck.store') }}" method="POST">
    @csrf
    <input type="hidden" name="transporter_id" id="quickTruckTransporterId" value="{{ $transporterId ?? '' }}">

    <div class="mb-3">
        <label class="col-form-label">Transporter</label>
        <input type="text" class="form-control" value="{{ $transporterName ?? '' }}" readonly disabled>
    </div>

    <div class="mb-3">
        <label class="col-form-label">
            Truck Number <span class="text-danger">*</span>
        </label>
        <input type="text" name="truck_number" class="form-control qt-truck-input" placeholder="e.g. GJ 01 AB 1234"
            style="text-transform: uppercase;" maxlength="50">
        <span class="text-danger small qt-truck-field-error" data-field="truck_number"></span>
    </div>

    <div class="mb-3">
        <label class="col-form-label">Status</label>
        <div class="d-flex align-items-center">
            <div class="me-3">
                <input type="radio" id="quick_truck_status_active" name="status" value="1" checked>
                <label for="quick_truck_status_active">Active</label>
            </div>
            <div>
                <input type="radio" id="quick_truck_status_inactive" name="status" value="0">
                <label for="quick_truck_status_inactive">Inactive</label>
            </div>
        </div>
        <span class="text-danger small qt-truck-field-error" data-field="status"></span>
    </div>
</form>
