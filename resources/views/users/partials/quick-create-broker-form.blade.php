<form id="quickBrokerForm" action="{{ route('users.store', 'broker') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="row">
        <div class="col-md-12">
            <div class="profile-pic-upload">
                <div class="profile-pic">
                    <img id="qb_profilePreview" src="{{ asset('images/default-user.png') }}" alt="Profile Picture"
                        class="img-thumbnail mb-2">
                </div>
                <div class="upload-content">
                    <div class="upload-btn">
                        <input type="file" name="profile_picture" accept=".jpg,.jpeg,.gif,.png"
                            class="qb-profile-input">
                        <span>
                            <i class="ti ti-file-broken"></i>Upload File
                        </span>
                    </div>
                    <p class="mb-0">JPG, JPEG, GIF or PNG. Max size of 2MB</p>
                    <span class="text-danger small qb-field-error" data-field="profile_picture"></span>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <label class="col-form-label">Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control qb-input" placeholder="Name" maxlength="255">
            <span class="text-danger small qb-field-error" data-field="name"></span>
        </div>

        <div class="col-md-6 mb-3">
            <label class="col-form-label">Email <span class="text-danger">*</span></label>
            <input type="text" name="email" class="form-control qb-input" placeholder="Email" maxlength="255">
            <span class="text-danger small qb-field-error" data-field="email"></span>
        </div>

        <div class="col-md-6 mb-3">
            <label class="col-form-label">Phone <span class="text-danger">*</span></label>
            <input type="number" name="phone_no" class="form-control qb-input" placeholder="Phone"
                oninput="this.value = this.value.slice(0, 10)">
            <span class="text-danger small qb-field-error" data-field="phone_no"></span>
        </div>

        <div class="col-md-6 mb-3">
            <label class="col-form-label">Password <span class="text-danger">*</span></label>
            <div class="icon-form-end">
                <span class="form-icon qb-toggle-pw" style="cursor:pointer;"><i class="ti ti-eye-off"></i></span>
                <input type="password" name="password" class="form-control qb-input" placeholder="Password">
            </div>
            <span class="text-danger small qb-field-error" data-field="password"></span>
        </div>

        <div class="col-md-6 mb-3">
            <label class="col-form-label">Confirm Password <span class="text-danger">*</span></label>
            <div class="icon-form-end">
                <span class="form-icon qb-toggle-pw" style="cursor:pointer;"><i class="ti ti-eye-off"></i></span>
                <input type="password" name="password_confirmation" class="form-control qb-input"
                    placeholder="Confirm Password">
            </div>
            <span class="text-danger small qb-field-error" data-field="password_confirmation"></span>
        </div>
    </div>
</form>
