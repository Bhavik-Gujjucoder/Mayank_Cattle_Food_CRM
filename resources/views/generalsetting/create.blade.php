@extends('layouts.main')
@section('content')
@section('title')
    {{ $page_title }}
@endsection
@php
    $activeTab = old('form_type', 'general-setting'); // fallback to first tab
@endphp
<div class="card">
    <div class="card-body">
        <!--ALL TABS-->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ $activeTab == 'general-setting' ? 'active' : '' }}" id="GeneralSetting"
                    data-bs-toggle="tab" href="#general-setting" role="tab" aria-controls="general-setting"
                    aria-selected="true{{-- $activeTab == 'general-setting' ? 'true' : 'false' --}}">General Setting</a>
            </li>

            <li class="nav-item" role="presentation">
                <a class="nav-link {{ $activeTab == 'company-detail' ? 'active' : '' }}" id="CompanyDetails"
                    data-bs-toggle="tab" href="#company-details" role="tab" aria-controls="company-details"
                    aria-selected="{{ $activeTab == 'company-detail' ? 'true' : 'false' }}">Company Details</a>
            </li>
        </ul>

        <div class="tab-content mt-3" id="myTabContent">
            <!--G E N E R A L   S E T T I N G   T A B-->
            <div class="tab-pane {{ $activeTab == 'general-setting' ? 'show active' : '' }}" id="general-setting"
                role="tabpanel" aria-labelledby="GeneralSetting">
                <form action="{{ route('generalsetting.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="form_type" value="general-setting">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="profile-pic-upload">
                                <div class="profile-pic">
                                    @if (getSetting('login_page_image') && !empty(getSetting('login_page_image')))
                                        <img id="loginPagePreview"
                                            src="{{ getSetting('login_page_image') ? asset('storage/login_page_image/' . getSetting('login_page_image')) : asset('images/default-user.png') }} "
                                            alt="Profile Picture"class="img-thumbnail mb-2" width="96.36px"
                                            height="100px" style="object-fit: contain" alt="Profile Picture">
                                    @endif
                                </div>
                                <div class="upload-content">
                                    <div class="upload-btn  @error('login_page_image') is-invalid @enderror">
                                        <input type="file" name="login_page_image" accept=".jpg,.jpeg,.gif,.png"
                                            onchange="previewProfilePicture(event, 'loginPagePreview')">
                                        <span>
                                            <i class="ti ti-file-broken"></i>Login Page Image
                                        </span>
                                    </div>
                                    <p>JPG, JPEG, GIF or PNG. Max size of 2MB</p>
                                    @error('login_page_image')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="col-form-label">Copyright Message<span
                                        class="text-danger"> *</span></label>
                                <textarea class="form-control @error('copyright_msg') is-invalid @enderror" name="copyright_msg"
                                    placeholder="Copyright Message">{{ old('copyright_msg', getSetting('copyright_msg')) }}</textarea>
                                @error('copyright_msg')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>

            <!--C O M P A N Y   D E T A I L S   T A B-->
            <div class="tab-pane fade {{ $activeTab == 'company-detail' ? 'show active' : '' }}" id="company-details"
                role="tabpanel" aria-labelledby="CompanyDetails">
                <form action="{{ route('generalsetting.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="form_type" value="company-detail">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="profile-pic-upload">
                                <div class="profile-pic">
                                    {{-- @if (getSetting('company_logo') && !empty(getSetting('company_logo'))) --}}
                                        <img id="companyLogoPreview"
                                            src="{{ getSetting('company_logo')
                                            ? asset('storage/company_logo/' . getSetting('company_logo'))
                                            : asset('images/default-user.png') }}"
                                            alt="Profile Picture"class="img-thumbnail mb-2" width="100%"
                                            height="100%" style="object-fit: contain" alt="Profile Picture">
                                    {{-- @endif --}}
                                </div>

                                <div class="upload-content">
                                    <div class="upload-btn @error('company_logo') is-invalid @enderror">
                                        <input type="file" name="company_logo" accept=".jpg,.jpeg,.gif,.png"
                                            onchange="previewProfilePicture(event, 'companyLogoPreview')">
                                        <span><i class="ti ti-file-broken"></i>Company Logo</span>
                                    </div>
                                    <p>JPG, JPEG, GIF or PNG. Max size of 2MB</p>
                                    @error('company_logo')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="col-form-label">Company Email Address <span
                                        class="text-danger">*</span></label>
                                <input type="text"
                                    class="form-control @error('company_email') is-invalid @enderror"
                                    name="company_email" value="{{ old('company_email', getSetting('company_email')) }}">
                                @error('company_email')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="col-form-label">Company Phone Number <span
                                        class="text-danger">*</span></label>
                                <input type="number"
                                    class="form-control @error('company_phone') is-invalid @enderror"
                                    name="company_phone" value="{{ old('company_phone', getSetting('company_phone')) }}"
                                    oninput="this.value = this.value.slice(0, 11)">
                                @error('company_phone')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="col-form-label">Company Address<span
                                        class="text-danger"> *</span></label>
                                <textarea class="form-control @error('company_address') is-invalid @enderror" name="company_address">{{ old('company_address', getSetting('company_address')) }}</textarea>
                                @error('company_address')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-end mt-3">
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </div>
                </form>
            </div>



        </div>
    </div>
</div>

@endsection
@section('script')
<script type="text/javascript">
    $(document).ready(function() {
        $('.summernote').summernote({
            tabsize: 2,
            height: 350
        });
    });
    function previewProfilePicture(event, targetId) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const image = document.getElementById(targetId);
                if (image) {
                    image.src = e.target.result;
                }
            };
            reader.readAsDataURL(file);
        }
    }




    // /***** Open Modal for Add a New product category *****/
    // $('#openModal').click(function() {
    //     $('#categoryForm')[0].reset();
    //     $('#adminModal').modal('show');
    //     $('#modalTitle').text('Add Parent Category');
    //     $('#submitBtn').text('Create');
    //     $('input[name="category_id"]').val('');
    //     $('select[name="parent_category_id"]').parent().show();
    //     $("#categoryForm .text-danger").text('');
    //     $('#categoryForm').find('.is-invalid').removeClass('is-invalid');
    // });

    // /***** Open Modal for Editing an Admin *****/
    // $(document).on('click', '.edit-btn', function() {
    //     let category_id = $(this).data('id');
    //     // alert(category_id);
    //     $("#categoryForm .text-danger").text('');
    //     $('#categoryForm').find('.is-invalid').removeClass('is-invalid');




    // function display_errors(errors) {
    //     $("#categoryForm .error-text").text('');
    //     $.each(errors, function(key, value) {
    //         $('input[name=' + key + ']').addClass('is-invalid');
    //         console.log($('input[name=' + key + ']'));
    //         $('.' + key + '_error').text(value[0]).addClass('text-danger');
    //     });
    // }


    // function confirmDeletion(callback) {
    //     Swal.fire({
    //         title: "Are you sure?",
    //         text: "You want to remove this category? Once deleted, it cannot be recovered.",
    //         icon: 'warning',
    //         showCancelButton: true,
    //         confirmButtonText: 'Yes, delete it!',
    //         cancelButtonText: 'Cancel',
    //         customClass: {
    //             popup: 'my-custom-popup',
    //             title: 'my-custom-title',
    //             confirmButton: 'btn btn-primary',
    //             cancelButton: 'btn btn-secondary',
    //             icon: 'my-custom-icon swal2-warning'
    //         }
    //     }).then((result) => {
    //         if (result.isConfirmed) {
    //             callback(); // Execute callback function if confirmed
    //         }
    //     });
    // }


</script>
@endsection
