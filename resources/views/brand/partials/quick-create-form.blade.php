<form id="quickBrandForm" action="{{ route('brand.store') }}" method="POST">
    @csrf

    <div class="mb-3">
        <label class="col-form-label">Brand Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control qb-brand-input" placeholder="Brand Name" maxlength="255">
        <span class="text-danger small qb-brand-field-error" data-field="name"></span>
    </div>
</form>
