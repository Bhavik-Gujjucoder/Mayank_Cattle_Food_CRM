@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('content')
    <div class="card">
        <div class="card-body">
            <form id="dealerForm" action="{{ route('dealer.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @if ($errors->any())
                    {{-- {{ dump($errors->first()) }} --}}
                @endif
                <div class="row">

                    {{-- ── Profile Image ───────────────────────────────── --}}
                    <div class="col-md-12">
                        <div class="profile-pic-upload">
                            <div class="profile-pic">
                                <img id="profilePreview" src="{{ asset('images/default-user.png') }}" alt="Profile Picture"
                                    class="img-thumbnail mb-2">
                                {{-- <span><i class="ti ti-photo"></i></span> --}}
                            </div>
                            <div class="upload-content">
                                <div class="upload-btn">
                                    <input type="file" name="profile_picture" accept=".jpg,.jpeg,.gif,.png"
                                        onchange="previewProfilePicture(event)"
                                        class="@error('profile_picture') is-invalid @enderror">
                                    <span>
                                        <i class="ti ti-file-broken"></i>Upload File
                                    </span>
                                    @error('profile_picture')
                                        <span class="invalid-feedback text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <p>JPG, JPEG, GIF or PNG. Max size of 2MB</p>
                            </div>
                        </div>
                    </div>

                    {{-- ── Broker ──────────────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Broker Person <span class="text-danger">*</span></label>
                        <select name="broker_id" id="broker_id"
                            class="form-select search-select @error('broker_id') is-invalid @enderror">
                            <option value="">-- Select Broker --</option>
                            @foreach ($brokers as $broker)
                                <option value="{{ $broker->id }}" {{ old('broker_id') == $broker->id ? 'selected' : '' }}>
                                    {{ $broker->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('broker_id')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        {{-- <span class="text-danger small" id="broker_id_error">
                            @error('broker_id')
                                {{ $message }}
                            @enderror
                        </span> --}}
                    </div>

                    {{-- ── Code No ─────────────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Code No <span class="text-danger">*</span></label>
                        <input type="text" name="code_no" id="code_no" value="{{ old('code_no', $code_no) }}"
                            class="form-control" readonly>
                        <span class="text-danger small" id="code_no_error">
                            @error('code_no')
                                {{ $message }}
                            @enderror
                        </span>
                    </div>

                    {{-- ── Applicant Name ──────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Applicant Name <span class="text-danger">*</span></label>
                        <input type="text" name="applicant_name" id="applicant_name" value="{{ old('applicant_name') }}"
                            class="form-control @error('applicant_name') is-invalid @enderror"
                            placeholder="Enter applicant name" maxlength="255">
                        @error('applicant_name')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        {{-- <span class="text-danger small" id="applicant_name_error">
                            @error('applicant_name')
                                {{ $message }}
                            @enderror
                        </span> --}}
                    </div>

                    {{-- ── Firm / Shop Name ────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Firm / Shop Name <span class="text-danger">*</span></label>
                        <input type="text" name="firm_shop_name" id="firm_shop_name" value="{{ old('firm_shop_name') }}"
                            class="form-control @error('firm_shop_name') is-invalid @enderror"
                            placeholder="Enter firm / shop name" maxlength="255">
                        @error('firm_shop_name')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        {{-- <span class="text-danger small" id="firm_shop_name_error">
                            @error('firm_shop_name')
                                {{ $message }}
                            @enderror
                        </span> --}}
                    </div>

                    {{-- ── Firm / Shop Address ─────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Firm / Shop Address <span class="text-danger">*</span></label>
                        <textarea name="firm_shop_address" id="firm_shop_address"
                            class="form-control @error('firm_shop_address') is-invalid @enderror" placeholder="Enter address" rows="2"
                            maxlength="500">{{ old('firm_shop_address') }}</textarea>
                        @error('firm_shop_address')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        {{-- <span class="text-danger small" id="firm_shop_address_error">
                            @error('firm_shop_address')
                                {{ $message }}
                            @enderror
                        </span> --}}
                    </div>



                    {{-- ── PAN Card ────────────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">PAN Card No <span class="text-danger">*</span></label>
                        <input type="text" name="pancard" id="pancard" value="{{ old('pancard') }}"
                            class="form-control @error('pancard') is-invalid @enderror" placeholder="e.g. ABCDE1234F"
                            maxlength="10" oninput="this.value = this.value.toUpperCase()">
                        @error('pancard')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        {{-- <span class="text-danger small" id="pancard_error">
                            @error('pancard')
                                {{ $message }}
                            @enderror
                        </span> --}}
                    </div>

                    {{-- ── GSTIN ───────────────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">GSTIN </label>
                        <input type="text" name="gstin" id="gstin" value="{{ old('gstin') }}"
                            class="form-control" placeholder="15-character GST number" maxlength="15"
                            oninput="this.value = this.value.toUpperCase()">
                        <span class="text-danger small" id="gstin_error">
                            @error('gstin')
                                {{ $message }}
                            @enderror
                        </span>
                    </div>
                    {{-- <small class="text-muted">(optional)</small> --}}
                    {{-- ── Aadhar Card ─────────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Aadhar Card No </label>
                        <input type="text" name="aadhar_card" id="aadhar_card" value="{{ old('aadhar_card') }}"
                            class="form-control" placeholder="12-digit Aadhar number" maxlength="12"
                            oninput="this.value = this.value.replace(/\D/g,'').slice(0,12)">
                        <span class="text-danger small" id="aadhar_card_error">
                            @error('aadhar_card')
                                {{ $message }}
                            @enderror
                        </span>
                    </div>

                    {{-- ── Mobile No ───────────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Mobile No <span class="text-danger">*</span></label>
                        <input type="text" name="mobile_no" id="mobile_no" value="{{ old('mobile_no') }}"
                            class="form-control @error('mobile_no') is-invalid @enderror"
                            placeholder="10-digit mobile number" maxlength="10"
                            oninput="this.value = this.value.replace(/\D/g,'').slice(0,10)">
                        @error('mobile_no')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        {{-- <span class="text-danger small" id="mobile_no_error">
                            @error('mobile_no')
                                {{ $message }}
                            @enderror
                        </span> --}}
                    </div>

                    {{-- ── Email ───────────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}"
                            class="form-control @error('email') is-invalid @enderror" placeholder="email">
                        @error('email')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        {{-- <span class="text-danger small" id="email_error">
                            @error('email')
                                {{ $message }}
                            @enderror
                        </span> --}}
                    </div>


                    <!-- Password Field -->
                    <div class="col-md-4 mb-3">
                        <div class="mb-3">
                            <label class="col-form-label">Password <span class="text-danger">*</span></label>
                            <div class="icon-form-end">
                                <span class="form-icon gc-icon-set"><i class="ti ti-eye-off"></i></span>
                                <input type="password" name="password"
                                    class="form-control @error('password') is-invalid @enderror" placeholder="Password">
                                @error('password')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                {{-- <span class="text-danger small" id="password_error">
                                    @error('password')
                                        {{ $message }}
                                    @enderror
                                </span> --}}
                            </div>
                        </div>
                    </div>

                    <!-- Confirm Password Field -->
                    <div class="col-md-4 mb-3">
                        <div class="mb-3">
                            <label class="col-form-label">Confirm Password <span class="text-danger">*</span></label>
                            <div class="icon-form-end">
                                <span class="form-icon"><i class="ti ti-eye-off"></i></span>
                                <input type="password" name="password_confirmation"
                                    class="form-control @error('password_confirmation') is-invalid @enderror"
                                    placeholder="Confirm Password">
                                @error('password_confirmation')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                {{-- <span class="text-danger small" id="password_confirmation_error">
                                    @error('password_confirmation')
                                        {{ $message }}
                                    @enderror
                                </span> --}}
                            </div>
                        </div>
                    </div>


                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="col-form-label">State/Province <span class="text-danger">*</span></label>
                            <select id="stateDropdown" class="form-select @error('state_id') is-invalid @enderror"
                                name="state_id">
                                <option value="">Select state</option>
                                @foreach ($states as $state)
                                    <option value="{{ $state->id }}"
                                        {{ old('state_id') == $state->id ? 'selected' : '' }}>
                                        {{ $state->state_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('state_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            {{-- <span id="state_id_error" class="text-danger"></span> --}}
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="col-form-label">City <span class="text-danger">*</span></label>
                            <select id="cityDropdown" class="form-select @error('city_id') is-invalid @enderror"
                                name="city_id">
                                <option value="">Select city</option>

                            </select>
                            @error('city_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            {{-- <span id="city_id_error" class="text-danger"></span> --}}
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="col-form-label">Postal Code </label>
                            <input type="text" name="postal_code" value="{{ old('postal_code') }}"
                                class="form-control @error('postal_code') is-invalid @enderror"
                                placeholder="Postal/Zip code" maxlength="255">
                            @error('postal_code')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            {{-- <span id="postal_code_error" class="text-danger"></span> --}}
                        </div>
                    </div>

                </div>{{-- /.row --}}

                <div class="d-flex justify-content-end mt-2">
                    <a href="{{ route('dealer.index') }}" class="btn btn-light me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Dealer</button>
                </div>

            </form>
        </div>
    </div>
@endsection
@section('script')
    <script>



        /**** State wise city dropdown ****/
        $(document).ready(function() {
            let oldCity = "{{ old('city_id') }}";
            console.log(oldCity);

            $('#stateDropdown').on('change', function() {
                var stateID = $(this).val();
                $('#cityDropdown').html('<option value="">Loading...</option>');
                if (stateID) {
                    $.ajax({
                        url: "{{ route('get.cities') }}",
                        type: "POST",
                        data: {
                            state_id: stateID,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(data) {
                            $('#cityDropdown').empty().append(
                                '<option value="">Select City</option>');
                            $.each(data, function(key, city) {
                                let selected = (oldCity == city.id) ? 'selected' : '';

                                $('#cityDropdown').append('<option value="' + city.id +
                                    '" ' + selected + '>' + city.city_name +
                                    '</option>');
                            });
                        }
                    });
                } else {
                    $('#cityDropdown').html('<option value="">-- Select City --</option>');
                }
            });
            if (oldCity) {
                $('#stateDropdown').trigger('change');
            }
        });
        /*** END ***/

        $(document).ready(function() {
            $(document).on('click', '.form-icon', function() {
                let input = $(this).siblings("input");
                let icon = $(this).find("i");

                if (input.attr("type") === "password") {
                    input.attr("type", "text");
                    icon.removeClass("ti-eye-off").addClass("ti-eye");
                } else {
                    input.attr("type", "password");
                    icon.removeClass("ti-eye").addClass("ti-eye-off");
                }
            });
        });

        /* Profile image preview */
        function previewImage(event) {
            var file = event.target.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }

        $(document).ready(function() {

            /* ── Select2 ─────────────────────────────────────────────── */
            $('#broker_id').select2({
                placeholder: '-- Select Broker --',
                width: '100%'
            });

            /* ── Custom validator: exact string length ───────────────── */
            $.validator.addMethod('exactlength', function(value, element, len) {
                return this.optional(element) || value.length === len;
            }, $.validator.format('Must be exactly {0} characters.'));

            /* ── Custom validator: PAN card format (AAAAA9999A) ─────── */
            $.validator.addMethod('panFormat', function(value, element) {
                return this.optional(element) || /^[A-Z]{5}[0-9]{4}[A-Z]$/.test(value);
            }, 'Invalid PAN format. Expected: AAAAA9999A');

            $.validator.addMethod('gstinFormat', function(value, element) {
                return this.optional(element) || /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/.test(
                    value);
            });

            /* ── jQuery Validate ─────────────────────────────────────── */
            // $('#dealerForm').validate({
            //     ignore: ':disabled',
            //     /* validate select2 hidden selects */
            //     rules: {
            //         broker_id: {
            //             required: true
            //         },
            //         code_no: {
            //             required: true
            //         },
            //         applicant_name: {
            //             required: true
            //         },
            //         firm_shop_name: {
            //             required: true
            //         },
            //         firm_shop_address: {
            //             required: true
            //         },
            //         mobile_no: {
            //             required: true,
            //             digits: true,
            //             exactlength: 10
            //         },
            //         email: {
            //             required: true,
            //         },
            //         pancard: {
            //             required: true,
            //             maxlength: 10,
            //             panFormat: true
            //         },
            //         gstin: {
            //             exactlength: 15,
            //             gstinFormat: true
            //         },
            //         aadhar_card: {
            //             digits: true,
            //             exactlength: 12
            //         },
            //         password: {
            //             required: true,
            //             minlength: 6
            //         },
            //         confirm_password: {
            //             required: true,
            //             equalTo: '[name="password"]'
            //         },
            //         state_id: {
            //             required: true,
            //         },
            //         city_id: {
            //             required: true,
            //         },
            //     },

            //     messages: {
            //         broker_id: 'Please select a broker.',
            //         code_no: 'Code no is required.',
            //         applicant_name: 'Applicant name is required.',
            //         firm_shop_name: 'Firm / shop name is required.',
            //         firm_shop_address: 'Address is required.',
            //         mobile_no: {
            //             required: 'Mobile no is required.',
            //             digits: 'Mobile no must be digits only.',
            //             exactlength: 'Mobile no must be exactly 10 digits.',
            //         },
            //         email: {
            //             required: 'Email is required.'
            //         },
            //         pancard: {
            //             required: 'PAN card no is required.',
            //             exactlength: 'PAN card must be exactly 10 characters.',
            //             panFormat: 'Invalid PAN. Format: AAAAA9999A',
            //         },
            //         gstin: {
            //             exactlength: 'GSTIN must be exactly 15 characters.',
            //             gstinFormat: 'Invalid GSTIN. Expected 15-character GST number.'
            //         },
            //         aadhar_card: {
            //             digits: 'Aadhar card must be digits only.',
            //             exactlength: 'Aadhar card must be exactly 12 digits.',
            //         },
            //         password: {
            //             required: "Password is required.",
            //             minlength: "Minimum 6 characters required."
            //         },
            //         confirm_password: {
            //             required: "Please confirm password.",
            //             equalTo: "Password does not match."
            //         },
            //         state_id: {
            //             required: "Please select state.",
            //         },
            //         city_id: {
            //             required: "Please select city.",
            //         },
            //     },

            //     /* Place error inside the matching *_error span */
            //     errorPlacement: function(error, element) {
            //         $('#' + element.attr('name') + '_error').html(error);
            //     },

            //     highlight: function(element) {
            //         $(element).addClass('is-invalid');
            //     },

            //     unhighlight: function(element) {
            //         $(element).removeClass('is-invalid');
            //         $('#' + $(element).attr('name') + '_error').html('');
            //     },

            //     /* Submit only when valid */
            //     submitHandler: function(form) {
            //         form.submit();
            //     },
            // });

            /* Select2 needs manual trigger for jQuery Validate */
            $('#broker_id').on('change', function() {
                $(this).valid();
            });
        });


        function previewProfilePicture(event) {
            const file = event.target.files[0]; // Get the selected file
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target
                        .result; // Set image preview source
                }
                reader.readAsDataURL(file); // Read the file as a Data URL
            }
        }
    </script>
@endsection
