@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('styles')
    @include('raw_material.partials.module-responsive')
@endsection
@section('content')

<div class="card raw-material-module">
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
                <div class="col-12 col-md-4 mb-3">
                    <label class="col-form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name', $raw_material->name) }}"
                           class="form-control" placeholder="Material name" maxlength="255">
                    <span class="text-danger small name_error">@error('name'){{ $message }}@enderror</span>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <label class="col-form-label">Unit <span class="text-danger">*</span></label>
                    <select name="unit" id="unit" class="form-select search-select">
                        <option value="">-- Select Unit --</option>
                        <option value="Ton" {{ old('unit', $raw_material->unit) == 'Ton' ? 'selected' : '' }}>Ton</option>
                        <option value="Kg" {{ old('unit', $raw_material->unit) == 'Kg' ? 'selected' : '' }}>Kg</option>
                    </select>
                    <span class="text-danger small unit_error">@error('unit'){{ $message }}@enderror</span>
                </div>
                <div class="col-12 col-md-4 mb-3">
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
                    <span class="text-danger small status_error">@error('status'){{ $message }}@enderror</span>
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

            <div class="d-flex align-items-center justify-content-end gap-2 mt-3 rm-form-actions">
                <a href="{{ route('raw-material.index') }}" class="btn btn-light px-4">Cancel</a>
                <button type="button" class="btn btn-primary px-5" id="submitMaterialBtn">Update Material</button>
            </div>
        </form>
    </div>
</div>

@endsection
@section('script')
@include('raw_material.partials.form-validation-script')
<script>
$(document).ready(function () {
    $('#unit').select2({ width: '100%' });

    function clearFieldErrors() {
        $('.name_error, .unit_error, .status_error').text('');
        rmSetInvalid($('#name'), false);
        rmSetInvalid($('#unit'), false);
    }

    $('#name').on('input', function () {
        $(this).removeClass('is-invalid');
        $('.name_error').text('');
    });

    $('#unit').on('change', function () {
        $(this).removeClass('is-invalid');
        $('.unit_error').text('');
    });

    $('input[name="status"]').on('change', function () {
        $('.status_error').text('');
    });

    function validateForm() {
        clearFieldErrors();
        var isValid = true;

        if (!$.trim($('#name').val())) {
            $('.name_error').text('Please enter material name.');
            rmSetInvalid($('#name'), true);
            isValid = false;
        }

        if (!$('#unit').val()) {
            $('.unit_error').text('Please select a unit.');
            rmSetInvalid($('#unit'), true);
            isValid = false;
        }

        if (!$('input[name="status"]:checked').length) {
            $('.status_error').text('Please select status.');
            isValid = false;
        }

        return isValid;
    }

    $('#submitMaterialBtn').on('click', function () {
        if (validateForm()) {
            $('#rawMaterialForm').submit();
        } else {
            rmScrollToFirstInvalid('#rawMaterialForm');
        }
    });
});
</script>
@endsection
