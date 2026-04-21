@extends('layouts.main')
@section('title') {{ $page_title }} @endsection
@section('content')
    <div class="card">
        <div class="card-body">
            <form id="dealerForm" action="{{ route('dealer.update', $dealer->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PATCH')

                <div class="row">

                    {{-- ── Profile Image ───────────────────────────────── --}}
                    <div class="col-md-12">
                        <div class="profile-pic-upload">
                            <div class="profile-pic">
                                <img id="profilePreview"
                                    src="{{ $dealer->user->profile_picture ? asset('storage/profile_pictures/' . $dealer->user->profile_picture) : asset('images/default-user.png') }}"
                                    alt="Profile Picture" class="img-thumbnail mb-2">
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
                                <p>JPG, JPEG, GIF or PNG. Max size of 2MB. Leave blank to keep current.</p>
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
                                <option value="{{ $broker->id }}"
                                    {{ old('broker_id', $dealer->broker_id) == $broker->id ? 'selected' : '' }}>
                                    {{ $broker->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('broker_id')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- ── Code No ─────────────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Code No <span class="text-danger">*</span></label>
                        <input type="text" name="code_no" id="code_no"
                            value="{{ old('code_no', $dealer->code_no) }}"
                            class="form-control" readonly>
                        <span class="text-danger small" id="code_no_error">
                            @error('code_no') {{ $message }} @enderror
                        </span>
                    </div>

                    {{-- ── Applicant Name ──────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Applicant Name <span class="text-danger">*</span></label>
                        <input type="text" name="applicant_name" id="applicant_name"
                            value="{{ old('applicant_name', $dealer->user->name) }}"
                            class="form-control @error('applicant_name') is-invalid @enderror"
                            placeholder="Enter applicant name" maxlength="255">
                        @error('applicant_name')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- ── Firm / Shop Name ────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Firm / Shop Name <span class="text-danger">*</span></label>
                        <input type="text" name="firm_shop_name" id="firm_shop_name"
                            value="{{ old('firm_shop_name', $dealer->firm_shop_name) }}"
                            class="form-control @error('firm_shop_name') is-invalid @enderror"
                            placeholder="Enter firm / shop name" maxlength="255">
                        @error('firm_shop_name')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- ── Firm / Shop Address ─────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Firm / Shop Address <span class="text-danger">*</span></label>
                        <textarea name="firm_shop_address" id="firm_shop_address"
                            class="form-control @error('firm_shop_address') is-invalid @enderror"
                            placeholder="Enter address" rows="2"
                            maxlength="500">{{ old('firm_shop_address', $dealer->firm_shop_address) }}</textarea>
                        @error('firm_shop_address')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- ── PAN Card ────────────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">PAN Card No <span class="text-danger">*</span></label>
                        <input type="text" name="pancard" id="pancard"
                            value="{{ old('pancard', $dealer->pancard) }}"
                            class="form-control @error('pancard') is-invalid @enderror"
                            placeholder="e.g. ABCDE1234F" maxlength="10"
                            oninput="this.value = this.value.toUpperCase()">
                        @error('pancard')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- ── GSTIN ───────────────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">GSTIN</label>
                        <input type="text" name="gstin" id="gstin"
                            value="{{ old('gstin', $dealer->gstin) }}"
                            class="form-control" placeholder="15-character GST number" maxlength="15"
                            oninput="this.value = this.value.toUpperCase()">
                        <span class="text-danger small" id="gstin_error">
                            @error('gstin') {{ $message }} @enderror
                        </span>
                    </div>

                    {{-- ── Aadhar Card ─────────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Aadhar Card No</label>
                        <input type="text" name="aadhar_card" id="aadhar_card"
                            value="{{ old('aadhar_card', $dealer->aadhar_card) }}"
                            class="form-control" placeholder="12-digit Aadhar number" maxlength="12"
                            oninput="this.value = this.value.replace(/\D/g,'').slice(0,12)">
                        <span class="text-danger small" id="aadhar_card_error">
                            @error('aadhar_card') {{ $message }} @enderror
                        </span>
                    </div>

                    {{-- ── Mobile No ───────────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Mobile No <span class="text-danger">*</span></label>
                        <input type="text" name="mobile_no" id="mobile_no"
                            value="{{ old('mobile_no', $dealer->user->phone_no) }}"
                            class="form-control @error('mobile_no') is-invalid @enderror"
                            placeholder="10-digit mobile number" maxlength="10"
                            oninput="this.value = this.value.replace(/\D/g,'').slice(0,10)">
                        @error('mobile_no')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- ── Email ───────────────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="email"
                            value="{{ old('email', $dealer->user->email) }}"
                            class="form-control @error('email') is-invalid @enderror"
                            placeholder="Email address">
                        @error('email')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- ── Password ────────────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Password</label>
                        <div class="icon-form-end">
                            <span class="form-icon gc-icon-set"><i class="ti ti-eye-off"></i></span>
                            <input type="password" name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                placeholder="Leave blank to keep current">
                            @error('password')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- ── Confirm Password ────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Confirm Password</label>
                        <div class="icon-form-end">
                            <span class="form-icon"><i class="ti ti-eye-off"></i></span>
                            <input type="password" name="password_confirmation"
                                class="form-control @error('password_confirmation') is-invalid @enderror"
                                placeholder="Leave blank to keep current">
                            @error('password_confirmation')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- ── State ───────────────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">State/Province <span class="text-danger">*</span></label>
                        <select id="stateDropdown" name="state_id"
                            class="form-select @error('state_id') is-invalid @enderror">
                            <option value="">Select state</option>
                            @foreach ($states as $state)
                                <option value="{{ $state->id }}"
                                    {{ old('state_id', $dealer->state_id) == $state->id ? 'selected' : '' }}>
                                    {{ $state->state_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('state_id')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- ── City ────────────────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">City <span class="text-danger">*</span></label>
                        <select id="cityDropdown" name="city_id"
                            class="form-select @error('city_id') is-invalid @enderror">
                            <option value="">Select city</option>
                            @foreach ($cities as $city)
                                <option value="{{ $city->id }}"
                                    {{ old('city_id', $dealer->city_id) == $city->id ? 'selected' : '' }}>
                                    {{ $city->city_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('city_id')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- ── Postal Code ─────────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Postal Code</label>
                        <input type="text" name="postal_code"
                            value="{{ old('postal_code', $dealer->postal_code) }}"
                            class="form-control @error('postal_code') is-invalid @enderror"
                            placeholder="Postal/Zip code" maxlength="6">
                        @error('postal_code')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                </div>{{-- /.row --}}

                <div class="d-flex justify-content-end mt-2">
                    <a href="{{ route('dealer.index') }}" class="btn btn-light me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Dealer</button>
                </div>

            </form>
        </div>
    </div>
@endsection
@section('script')
<script>
    /**** State-wise city dropdown ****/
    $(document).ready(function () {
        let currentCity = "{{ old('city_id', $dealer->city_id) }}";

        $('#stateDropdown').on('change', function () {
            var stateID = $(this).val();
            $('#cityDropdown').html('<option value="">Loading...</option>');
            if (stateID) {
                $.ajax({
                    url: "{{ route('get.cities') }}",
                    type: "POST",
                    data: { state_id: stateID, _token: "{{ csrf_token() }}" },
                    success: function (data) {
                        $('#cityDropdown').empty().append('<option value="">Select City</option>');
                        $.each(data, function (key, city) {
                            let selected = (currentCity == city.id) ? 'selected' : '';
                            $('#cityDropdown').append(
                                '<option value="' + city.id + '" ' + selected + '>' + city.city_name + '</option>'
                            );
                        });
                    }
                });
            } else {
                $('#cityDropdown').html('<option value="">-- Select City --</option>');
            }
        });

        /* Trigger on load only when the city list wasn't pre-rendered (e.g. after a validation error) */
        @if(old('state_id') && !old('city_id'))
            $('#stateDropdown').trigger('change');
        @endif
    });
    /*** END ***/

    /* Password toggle */
    $(document).ready(function () {
        $(document).on('click', '.form-icon', function () {
            let input = $(this).siblings('input');
            let icon  = $(this).find('i');
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('ti-eye-off').addClass('ti-eye');
            } else {
                input.attr('type', 'password');
                icon.removeClass('ti-eye').addClass('ti-eye-off');
            }
        });
    });

    /* Profile picture preview */
    function previewProfilePicture(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('profilePreview').src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    }

    $(document).ready(function () {
        /* Select2 for broker dropdown */
        $('#broker_id').select2({ placeholder: '-- Select Broker --', width: '100%' });

        $('#broker_id').on('change', function () { $(this).valid(); });
    });
</script>
@endsection
