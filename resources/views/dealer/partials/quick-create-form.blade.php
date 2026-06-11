<form id="quickDealerForm" action="{{ route('dealer.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <input type="hidden" name="broker_id" value="{{ $lockedBrokerId }}">
    <input type="hidden" name="brand_id" value="{{ $lockedBrandId }}">

    <div class="row">

        <div class="col-md-12">
            <div class="profile-pic-upload">
                <div class="profile-pic">
                    <img id="qd_profilePreview" src="{{ asset('images/default-user.png') }}" alt="Profile Picture"
                        class="img-thumbnail mb-2">
                </div>
                <div class="upload-content">
                    <div class="upload-btn">
                        <input type="file" name="profile_picture" accept=".jpg,.jpeg,.gif,.png"
                            class="qd-profile-input">
                        <span>
                            <i class="ti ti-file-broken"></i>Upload File
                        </span>
                    </div>
                    <p class="mb-0">JPG, JPEG, GIF or PNG. Max size of 2MB</p>
                    <span class="text-danger small qd-field-error" data-field="profile_picture"></span>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <label class="col-form-label">Code No <span class="text-danger">*</span></label>
            <input type="text" name="code_no" value="{{ $code_no }}" class="form-control" readonly>
            <span class="text-danger small qd-field-error" data-field="code_no"></span>
        </div>

        <div class="col-md-4 mb-3">
            <label class="col-form-label">Broker Person <span class="text-danger">*</span></label>
            <input type="text" class="form-control" value="{{ $broker->name }}" readonly disabled>
        </div>

        <div class="col-md-4 mb-3">
            <label class="col-form-label">Brand <span class="text-danger">*</span></label>
            <input type="text" class="form-control" value="{{ $brand->name }}" readonly disabled>
        </div>

        <div class="col-md-4 mb-3">
            <label class="col-form-label">Applicant Name <span class="text-danger">*</span></label>
            <input type="text" name="applicant_name" class="form-control qd-input"
                placeholder="Enter applicant name" maxlength="255">
            <span class="text-danger small qd-field-error" data-field="applicant_name"></span>
        </div>

        <div class="col-md-4 mb-3">
            <label class="col-form-label">Firm / Shop Name <span class="text-danger">*</span></label>
            <input type="text" name="firm_shop_name" class="form-control qd-input"
                placeholder="Enter firm / shop name" maxlength="255">
            <span class="text-danger small qd-field-error" data-field="firm_shop_name"></span>
        </div>

        <div class="col-md-4 mb-3">
            <label class="col-form-label">Firm / Shop Address <span class="text-danger">*</span></label>
            <textarea name="firm_shop_address" class="form-control qd-input" placeholder="Enter address"
                rows="2" maxlength="500"></textarea>
            <span class="text-danger small qd-field-error" data-field="firm_shop_address"></span>
        </div>

        <div class="col-md-4 mb-3">
            <label class="col-form-label">PAN Card No</label>
            <input type="text" name="pancard" class="form-control qd-input" placeholder="e.g. ABCDE1234F"
                maxlength="10" oninput="this.value = this.value.toUpperCase()">
            <span class="text-danger small qd-field-error" data-field="pancard"></span>
        </div>

        <div class="col-md-4 mb-3">
            <label class="col-form-label">GSTIN</label>
            <input type="text" name="gstin" class="form-control qd-input" placeholder="15-character GST number"
                maxlength="15" oninput="this.value = this.value.toUpperCase()">
            <span class="text-danger small qd-field-error" data-field="gstin"></span>
        </div>

        <div class="col-md-4 mb-3">
            <label class="col-form-label">Aadhar Card No</label>
            <input type="text" name="aadhar_card" class="form-control qd-input" placeholder="12-digit Aadhar number"
                maxlength="12" oninput="this.value = this.value.replace(/\D/g,'').slice(0,12)">
            <span class="text-danger small qd-field-error" data-field="aadhar_card"></span>
        </div>

        <div class="col-md-4 mb-3">
            <label class="col-form-label">Mobile No <span class="text-danger">*</span></label>
            <input type="text" name="mobile_no" class="form-control qd-input" placeholder="10-digit mobile number"
                maxlength="10" oninput="this.value = this.value.replace(/\D/g,'').slice(0,10)">
            <span class="text-danger small qd-field-error" data-field="mobile_no"></span>
        </div>

        <div class="col-md-4 mb-3">
            <label class="col-form-label">Email</label>
            <input type="email" name="email" class="form-control qd-input" placeholder="email">
            <span class="text-danger small qd-field-error" data-field="email"></span>
        </div>

        <div class="col-md-4 mb-3">
            <label class="col-form-label">Password <span class="text-danger">*</span></label>
            <div class="icon-form-end">
                <span class="form-icon qd-toggle-pw"><i class="ti ti-eye-off"></i></span>
                <input type="password" name="password" class="form-control qd-input" placeholder="Password">
            </div>
            <span class="text-danger small qd-field-error" data-field="password"></span>
        </div>

        <div class="col-md-4 mb-3">
            <label class="col-form-label">Confirm Password <span class="text-danger">*</span></label>
            <div class="icon-form-end">
                <span class="form-icon qd-toggle-pw"><i class="ti ti-eye-off"></i></span>
                <input type="password" name="password_confirmation" class="form-control qd-input"
                    placeholder="Confirm Password">
            </div>
            <span class="text-danger small qd-field-error" data-field="password_confirmation"></span>
        </div>

        <div class="col-md-4 mb-3">
            <label class="col-form-label">State/Province <span class="text-danger">*</span></label>
            <select id="qd_stateDropdown" class="form-select qd-input" name="state_id">
                <option value="">Select state</option>
                @foreach ($states as $state)
                    <option value="{{ $state->id }}">{{ $state->state_name }}</option>
                @endforeach
            </select>
            <span class="text-danger small qd-field-error" data-field="state_id"></span>
        </div>

        <div class="col-md-4 mb-3">
            <label class="col-form-label">City <span class="text-danger">*</span></label>
            <select id="qd_cityDropdown" class="form-select qd-input" name="city_id">
                <option value="">Select city</option>
            </select>
            <span class="text-danger small qd-field-error" data-field="city_id"></span>
        </div>

        <div class="col-md-4 mb-3">
            <label class="col-form-label">Postal Code</label>
            <input type="text" name="postal_code" class="form-control qd-input" placeholder="Postal/Zip code"
                maxlength="6">
            <span class="text-danger small qd-field-error" data-field="postal_code"></span>
        </div>

    </div>
</form>
