@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('content')

<div class="card">
    <div class="card-body">
        <form action="{{ route('raw-material.store') }}" method="POST" id="rawMaterialForm">
            @csrf
            <p class="form-section-title"><i class="ti ti-packages me-1"></i>Raw Material Details</p>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="col-form-label">Material ID</label>
                    <input type="text" name="raw_material_unique_id" value="{{ old('raw_material_unique_id', $raw_material_unique_id) }}"
                           class="form-control fw-semibold" readonly>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="col-form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="form-control @error('name') is-invalid @enderror" placeholder="Material name" maxlength="255">
                    @error('name')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="col-form-label">Unit <span class="text-danger">*</span></label>
                    <select name="unit" class="form-select search-select @error('unit') is-invalid @enderror">
                        <option value="">-- Select Unit --</option>
                        <option value="Ton" {{ old('unit', 'Ton') == 'Ton' ? 'selected' : '' }}>Ton</option>
                        <option value="Kg" {{ old('unit') == 'Kg' ? 'selected' : '' }}>Kg</option>
                    </select>
                    @error('unit')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="col-form-label">Status <span class="text-danger">*</span></label>
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <input type="radio" id="status_active" name="status" value="1"
                                   {{ old('status', '1') == '1' ? 'checked' : '' }}>
                            <label for="status_active">Active</label>
                        </div>
                        <div>
                            <input type="radio" id="status_inactive" name="status" value="0"
                                   {{ old('status') == '0' ? 'checked' : '' }}>
                            <label for="status_inactive">Inactive</label>
                        </div>
                    </div>
                    @error('status')
                        <span class="text-danger small d-block">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="d-flex align-items-center justify-content-end gap-2 mt-3">
                <a href="{{ route('raw-material.index') }}" class="btn btn-light px-4">Cancel</a>
                <button type="submit" class="btn btn-primary px-5">Save Material</button>
            </div>
        </form>
    </div>
</div>

@endsection
@section('script')
<script>
$(document).ready(function () {
    $('.search-select').select2({ width: '100%' });
});
</script>
@endsection
