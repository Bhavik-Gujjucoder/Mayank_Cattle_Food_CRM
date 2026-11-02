<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ $action }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="{{ $modalId }}Label">{{ $title }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Download the sample file, replace the example rows, then upload it here.</p>
                    <p class="mb-3">
                        <a href="{{ $sampleUrl }}"><i class="ti ti-download me-1"></i>Download sample Excel</a>
                    </p>
                    <label class="form-label" for="{{ $modalId }}File">Excel file</label>
                    <input type="file" class="form-control" id="{{ $modalId }}File" name="file" accept=".xlsx,.xls,.csv" required>
                    <small class="text-muted d-block mt-2">Nothing is saved if any row has an error.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
