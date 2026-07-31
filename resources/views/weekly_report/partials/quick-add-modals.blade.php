@can('add-transporter')
<div class="modal fade" id="wrQuickTransporterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title"><i class="ti ti-user-plus me-2"></i>Add Transporter</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="wrQuickTransporterModalBody">
                <div class="text-center text-muted py-4">
                    <i class="ti ti-loader-2 ti-spin fs-3"></i>
                    <p class="mb-0 mt-2">Loading form…</p>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="wrQuickTransporterSubmitBtn">
                    <i class="ti ti-check me-1"></i>Save
                </button>
            </div>
        </div>
    </div>
</div>
@endcan

@can('add-truck')
<div class="modal fade" id="wrQuickTruckModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title"><i class="ti ti-truck me-2"></i>Add Truck</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="wrQuickTruckModalBody">
                <div class="text-center text-muted py-4">
                    <i class="ti ti-loader-2 ti-spin fs-3"></i>
                    <p class="mb-0 mt-2">Loading form…</p>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="wrQuickTruckSubmitBtn">
                    <i class="ti ti-check me-1"></i>Save
                </button>
            </div>
        </div>
    </div>
</div>
@endcan
