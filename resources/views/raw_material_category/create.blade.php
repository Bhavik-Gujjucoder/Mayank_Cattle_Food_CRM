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
        <form action="{{ route('raw-material.category.store') }}" method="POST" id="categoryForm">
            @csrf
            <p class="form-section-title"><i class="ti ti-category me-1"></i>Category Details</p>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="col-form-label">Category ID</label>
                    <input type="text" value="{{ old('category_unique_id', $category_unique_id) }}"
                           class="form-control fw-semibold" readonly>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <label class="col-form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}"
                           class="form-control" placeholder="Category name" maxlength="255">
                    <span class="text-danger small name_error">@error('name'){{ $message }}@enderror</span>
                </div>
                <div class="col-12 col-md-4 mb-3">
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
                    <span class="text-danger small status_error">@error('status'){{ $message }}@enderror</span>
                </div>
            </div>
            <div class="d-flex align-items-center justify-content-end gap-2 mt-3 rm-form-actions">
                <a href="{{ route('raw-material.category.index') }}" class="btn btn-light px-4">Cancel</a>
                <button type="button" class="btn btn-primary px-5" id="submitCategoryBtn">Save Category</button>
            </div>
        </form>
    </div>
</div>

@endsection
@section('script')
@include('raw_material.partials.form-validation-script')
<script>
$(function () {
    $('#submitCategoryBtn').on('click', function () {
        var ok = true;
        $('.name_error, .status_error').text('');
        rmSetInvalid($('#name'), false);
        if (!$.trim($('#name').val())) {
            $('.name_error').text('Please enter category name.');
            rmSetInvalid($('#name'), true);
            ok = false;
        }
        if (!$('input[name="status"]:checked').length) {
            $('.status_error').text('Please select status.');
            ok = false;
        }
        if (ok) $('#categoryForm').submit();
        else rmScrollToFirstInvalid('#categoryForm');
    });
});
</script>
@endsection
