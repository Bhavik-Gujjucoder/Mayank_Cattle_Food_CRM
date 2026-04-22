@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('order.store') }}" id="orderForm" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row mb-4 order-form">
                <div class="col-md-4 mb-3">
                    <label class="col-form-label">Order ID</label>
                    <input type="text" name="unique_order_id" value="ORD000001" class="form-control"
                        readonly>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="col-form-label">Broker <span class="text-danger">*</span></label>
                    <select name="broker_id" id="broker_id" class="form-control form-select search-dropdown">
                        <option value="">Select</option>
                        <option value="1">Tanmay Trading</option>
                        <option value="1">Raj Traders</option>
                        <option value="1">Tirupati Trading</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="col-form-label">Dealer <span class="text-danger">*</span></label>
                    <select name="dealer_id" id="dealer_id" class="form-control form-select search-dropdown">
                        <option value="">Select</option>
                        <option value="1">Shyam Trading</option>
                        <option value="1">Radhe Dealers</option>
                        <option value="1">Ankur Trading</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="col-form-label">Transporter <span class="text-danger">*</span></label>
                    <select name="dealer_id" id="dealer_id" class="form-control form-select search-dropdown">
                        <option value="">Select</option>
                        <option value="1">Tirupati Transport</option>
                        <option value="1">Radhe Transport</option>
                        <option value="1">Maruti Transport</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="col-form-label">Transporter Amount <span class="text-danger">*</span></label>
                    <input type="text" name="order_date" value="" id="datePicker"
                        class="form-control" placeholder="Transporter Amount">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="col-form-label">Order Date <span class="text-danger">*</span></label>
                    <div class="icon-form">
                        <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
                        <input type="text" name="order_date" value="" id="datePicker"
                            class="form-control" placeholder="Order Date">
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="col-form-label">Expected Delivery Date <span class="text-danger">*</span></label>
                    <div class="icon-form">
                        <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
                        <input type="text" name="order_date" value="" id="datePicker"
                            class="form-control" placeholder="Order Date">
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="col-form-label">Purchase Location <span class="text-danger">*</span></label>
                    <input type="text" name="transport" value="" class="form-control"
                        placeholder="Purchase Location">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="col-form-label">Delivery Location <span class="text-danger">*</span></label>
                    <input type="text" name="transport" value="" class="form-control"
                        placeholder="Delivery Location">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="col-form-label">Priority <span class="text-danger">*</span></label>
                    <select name="dealer_id" id="dealer_id" class="form-control form-select search-dropdown">
                        <option value="">Select</option>
                        <option value="1">Normal</option>
                        <option value="1">Medium</option>
                        <option value="1">High</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="col-form-label d-block">Advance Payment Discount </label>
                    <div class="form-check form-check-inline">
                        <input type="hidden" name="advance_payment_discount" value="no">
                        <input class="form-check-input advance-payment-discount" type="checkbox"
                            name="advance_payment_discount" id="advance_payment_discount" value="yes">
                        <label class="form-check-label" for="advance_payment_discount">Advance Payment
                            Discount 25%

                        </label>
                    </div>
                    <input type="hidden" name="payment_discount"
                        value="{{ getSetting('advance_payment_discount') }}">
                    <input type="hidden" name="discount_type" value="{{ getSetting('discount_type') }}">
                    <!-- Image Field -->
                </div>
                <div class="col-md-4 mb-3 advance-payment-discount-image-field" style="display: none;">
                    <label class="col-form-label"> Upload Image <span class="text-danger">*</span></label>
                    <input type="file" class="form-control" name="advance_pay_discount_img" accept="image/*">
                </div>
            </div>
            <input type="hidden" name="dummy" id="dummyValidationField" />

            <div class="table-responsive gc-order-management-table">
                <table class="table table-view addnewfield">
                    <thead>
                        <tr>
                            <th scope="col">S.No </th>
                            <th scope="col">Product Name <span class="text-danger">*</span></th>
                            <th scope="col">GST(%)</th>
                            <th scope="col">QTY <span class="text-danger">*</span></th>
                            <th scope="col">Unit Price <span class="text-danger">*</span></th>
                            <th scope="col">Total Price <span class="text-danger">*</span></th>
                            <th scope="col">Action </th>
                        </tr>
                    </thead>
                    <tbody id="table-body">
                        <tr class="field-group">
                            <td data-label="S.No.">1</td>
                            <td data-label="Product Name">
                                <select name="product_id[]"
                                    class="form-control product-field form-select product_id-field search-dropdown">
                                    <option selected disabled>Select</option>
                                        <option value="1" data-gst="1">Cottonseed Cake</option>
                                        <option value="2" data-gst="2">Soybean Meal</option>
                                        <option value="3" data-gst="3">Wheat Straw (Bhusa)</option>
                                        <option value="4" data-gst="4">Other</option>
                                </select>
                            </td>
                            <td data-label="GST">
                                <input type="number" name="gst[]" value="" class="form-control gst-field"
                                    readonly placeholder="GST">
                            </td>
                            <td data-label="QTY">
                                <input type="number" name="qty[]" value="{{ old('qty') }}"
                                    class="form-control product-field qty-field" placeholder="QTY">
                            </td>
                            <td data-label="Price">
                                <input type="number" name="price[]" value="{{ old('price') }}" readonly
                                    class="form-control product-field price-field" placeholder="Price">
                            </td>
                            <td data-label="Total">
                                <input type="number" name="total[]" class="form-control total-field" readonly
                                    placeholder="Total">
                            </td>
                            <td data-label="Action">
                                <button type="button" onclick="addpropRow()" class="btn btn-primary">Add
                                    New</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div id="productError" class="text-danger mb-3" style="display:none;">
                    Please fill all fields in each product row.
                </div>
            </div>
            <div class="gstsec mt-4 mb-4">
                <div class="totalsec text-end">
                    <input type="hidden" name="total_order_amount" value="">
                    {{-- <div class="row">
                        <div class="col-md-12">
                            <label class="col-form-label" id="all_total">Total : 0</label>
                        </div>
                    </div>
                     <div class="row">
                        <div class="col-md-12">
                            <label class="col-form-label">GST {{ getSetting('gst') }}% : <span
                                    id="gstAmount">0</span></label>
                            <input type="hidden" name="gst" value="{{ getSetting('gst') }}">
                            <input type="hidden" name="gst_amount" value="">
                        </div>
                    </div> --}}
                    <div class="row">
                        <div class="col-md-12">
                            <label class="col-form-label" id="product_total_order_amount">Total Amount : 0 </label>
                            <label class="col-form-label" id="discount">Discount : 0 </label>
                            <label class="col-form-label" id="grand_total">Grand Total (Incl. GST) : 0</label>
                            <input type="hidden" name="grand_total" value="">
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center justify-content-end">
                <button type="submit" class="btn btn-primary">Create</button>
            </div>
        </form>
    </div>
</div>
@endsection
@section('script')
    <script>
        $(document).ready(function() {
        advancePaymentDiscount();

        /*** advance payment discount checked or not***/
        function advancePaymentDiscount() {
            const advancePaymentDiscount = $('input[name="advance_payment_discount"]:checked').val();
            if (advancePaymentDiscount === 'yes') {
                $('.advance-payment-discount-image-field').show();
            } else {
                $('.advance-payment-discount-image-field').hide();
            }
        }
        $(document).on('change', '.advance-payment-discount', function() {
            advancePaymentDiscount();
            calculateGrandTotal();
        })
    });
    </script>
@endsection