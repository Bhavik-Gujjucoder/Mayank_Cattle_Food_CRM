@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('content')

<div class="card">
    <div class="card-body">
        <form action="{{ route('raw-material.update', $raw_material->id) }}" method="POST" id="rawMaterialForm">
            @csrf
            @method('PUT')
            <p class="form-section-title"><i class="ti ti-packages me-1"></i>Raw Material Details</p>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="col-form-label">Material ID</label>
                    <input type="text" value="{{ $raw_material->raw_material_unique_id }}"
                           class="form-control fw-semibold" readonly>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="col-form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $raw_material->name) }}"
                           class="form-control @error('name') is-invalid @enderror" placeholder="Material name" maxlength="255">
                    @error('name')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="col-form-label">Unit <span class="text-danger">*</span></label>
                    <select name="unit" class="form-select search-select @error('unit') is-invalid @enderror">
                        <option value="">-- Select Unit --</option>
                        <option value="Ton" {{ old('unit', $raw_material->unit) == 'Ton' ? 'selected' : '' }}>Ton</option>
                        <option value="Kg" {{ old('unit', $raw_material->unit) == 'Kg' ? 'selected' : '' }}>Kg</option>
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
                                   {{ old('status', $raw_material->status) == '1' ? 'checked' : '' }}>
                            <label for="status_active">Active</label>
                        </div>
                        <div>
                            <input type="radio" id="status_inactive" name="status" value="0"
                                   {{ old('status', $raw_material->status) == '0' ? 'checked' : '' }}>
                            <label for="status_inactive">Inactive</label>
                        </div>
                    </div>
                    @error('status')
                        <span class="text-danger small d-block">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <p class="form-section-title mt-2"><i class="ti ti-chart-bar me-1"></i>Stock &amp; Pricing (Read Only)</p>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="col-form-label">Total Stock</label>
                    <input type="text" class="form-control" readonly
                           value="{{ number_format($raw_material->total_stock, 2) }} {{ $raw_material->unit }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="col-form-label">Available Stock</label>
                    <input type="text" class="form-control" readonly
                           value="{{ number_format($raw_material->available_stock, 2) }} {{ $raw_material->unit }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="col-form-label">Last Purchase Price</label>
                    <input type="text" class="form-control" readonly
                           value="₹ {{ number_format($raw_material->last_purchase_price, 2) }} / kg">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="col-form-label">Average Price</label>
                    <input type="text" class="form-control" readonly
                           value="₹ {{ number_format($raw_material->average_price, 2) }} / kg">
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-end gap-2 mt-3">
                <a href="{{ route('raw-material.index') }}" class="btn btn-light px-4">Cancel</a>
                <button type="submit" class="btn btn-primary px-5">Update Material</button>
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
