<div class="table-responsive">
    <table class="table table-bordered order-product-table" id="itemTable">
        <thead>
            <tr>
                <th style="width:50px;">S.No</th>
                <th style="min-width:160px;">Category <span class="text-danger">*</span></th>
                <th style="min-width:180px;">Raw Material <span class="text-danger">*</span></th>
                <th style="width:110px;">Total Qty (tons) <span class="text-danger">*</span></th>
                <th style="width:140px;">Price / kg <span class="text-danger">*</span></th>
                <th style="width:140px;">Total Price</th>
                <th style="width:140px;">Other Expense</th>
                <th style="width:100px;" class="text-center">Action</th>
            </tr>
        </thead>
        <tbody id="itemTableBody">
            <tr class="item-row">
                <td class="row-index text-center fw-semibold">1</td>
                <td>
                    <select class="form-select category-select" style="min-width:150px;">
                        <option value="">-- Select Category --</option>
                    </select>
                </td>
                <td>
                    <select name="raw_material_id[]" class="form-select material-select" style="min-width:170px;" disabled>
                        <option value="">-- Select Material --</option>
                    </select>
                </td>
                <td>
                    <input type="number" name="total_qty[]" class="form-control qty-field" placeholder="0" min="1" step="1">
                </td>
                <td>
                    <input type="number" name="price[]" class="form-control price-field" placeholder="0.00" min="0" step="0.001">
                </td>
                <td>
                    <input type="text" class="form-control total-field" placeholder="0.00" readonly>
                </td>
                <td>
                    <input type="number" name="other_expense[]" class="form-control other-expense-field" placeholder="0.00" min="0" step="0.01" value="0">
                </td>
                <td class="text-center row-actions">
                    <button type="button" class="btn btn-primary btn-sm" id="addRowBtn">
                        <i class="ti ti-plus me-1"></i>Add New
                    </button>
                    <button type="button" class="btn-remove-row remove-row-btn" title="Remove row" style="display:none;">
                        <i class="ti ti-trash"></i>
                    </button>
                </td>
            </tr>
            <tr class="item-row-error" style="display:none;">
                <td colspan="8" class="pt-0 pb-2 border-top-0">
                    <small class="text-danger">
                        <i class="ti ti-alert-circle me-1"></i>Category, material, qty and price are required.
                    </small>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<template id="itemRowTpl">
    <tr class="item-row">
        <td class="row-index text-center fw-semibold"></td>
        <td>
            <select class="form-select category-select" style="min-width:150px;">
                <option value="">-- Select Category --</option>
            </select>
        </td>
        <td>
            <select name="raw_material_id[]" class="form-select material-select" style="min-width:170px;" disabled>
                <option value="">-- Select Material --</option>
            </select>
        </td>
        <td>
            <input type="number" name="total_qty[]" class="form-control qty-field" placeholder="0" min="1" step="1">
        </td>
        <td>
            <input type="number" name="price[]" class="form-control price-field" placeholder="0.00" min="0" step="0.001">
        </td>
        <td>
            <input type="text" class="form-control total-field" placeholder="0.00" readonly>
        </td>
        <td>
            <input type="number" name="other_expense[]" class="form-control other-expense-field" placeholder="0.00" min="0" step="0.01" value="0">
        </td>
        <td class="text-center row-actions">
            <button type="button" class="btn btn-primary btn-sm" id="addRowBtn">
                <i class="ti ti-plus me-1"></i>Add New
            </button>
            <button type="button" class="btn-remove-row remove-row-btn" title="Remove row" style="display:none;">
                <i class="ti ti-trash"></i>
            </button>
        </td>
    </tr>
    <tr class="item-row-error" style="display:none;">
        <td colspan="8" class="pt-0 pb-2 border-top-0">
            <small class="text-danger">
                <i class="ti ti-alert-circle me-1"></i>Category, material, qty and price are required.
            </small>
        </td>
    </tr>
</template>
