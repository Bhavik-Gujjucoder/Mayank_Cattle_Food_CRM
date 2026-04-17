@extends('layouts.main')
@section('content')
@section('title')
    {{ $page_title }}
@endsection
<div class="card">
    <div class="card-body">
        <form action="{{ route('users.store',$type) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- Basic Info -->
            <div class="manage-user icon-set">
                <div class="row">
                    <div class="col-md-12">
                        <div class="profile-pic-upload">
                            <div class="profile-pic">
                                <img id="profilePreview" src="{{ asset('images/default-user.png') }}"
                                alt="Profile Picture" class="img-thumbnail mb-2">
                                {{-- <span><i class="ti ti-photo"></i></span> --}}
                            </div>
                            <div class="upload-content">
                                <div class="upload-btn">
                                    <input type="file" name="profile_picture" accept=".jpg,.jpeg,.gif,.png" onchange="previewProfilePicture(event)">
                                    <span>
                                        <i class="ti ti-file-broken"></i>Upload File
                                    </span>
                                </div>
                                <p>JPG, JPEG, GIF or PNG. Max size of 2MB</p>
                            </div>
                        </div>
                    </div>

                    <!-- Name Field -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="col-form-label"> Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                name="name" value="{{ old('name') }}" placeholder="Name" maxlength="255">
                            @error('name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Email Field -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="col-form-label">Email <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('email') is-invalid @enderror"
                                name="email" value="{{ old('email') }}" placeholder="Email" maxlength="255">
                            @error('email')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    @if($type == 'user')

                    <!-- Role Field -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="col-form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-control @error('role') is-invalid @enderror" name="role">
                                <option value="">Select Role</option>
                                @foreach ($roles as $key => $role)
                                    @if ($role != 'super admin')
                                        <!-- Exclude super admin -->
                                        <option value="{{ $key }}" {{ old('role') == $key ? 'selected' : '' }}>
                                            {{ $role }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            @error('role')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    @endif

                    <!-- Phone Field -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="col-form-label">Phone <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('phone_no') is-invalid @enderror"
                                name="phone_no" value="{{ old('phone_no') }}" placeholder="Phone"
                                oninput="this.value = this.value.slice(0, 10)">
                            @error('phone_no')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="col-form-label">Password <span class="text-danger">*</span></label>
                            <div class="icon-form-end">
                                <span class="form-icon gc-icon-set"><i class="ti ti-eye-off"></i></span>
                                <input type="password" class="form-control icone @error('password') is-invalid @enderror"
                                    name="password" placeholder="Password">
                                    @error('password')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Confirm Password Field -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="col-form-label">Confirm Password <span class="text-danger">*</span></label>
                            <div class="icon-form-end">
                                <span class="form-icon"><i class="ti ti-eye-off"></i></span>
                                <input type="password"
                                    class="form-control @error('password_confirmation') is-invalid @enderror"
                                    name="password_confirmation" placeholder="Confirm Password">
                                    @error('password_confirmation')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Status Field -->
                    <div class="col-md-6">
                        <div class="radio-wrap">
                            <label class="col-form-label">Status</label>
                            <div class="d-flex align-items-center">
                                <div class="me-2">
                                    <input type="radio" class="status-radio" id="active1" name="status"
                                        value="1" {{ old('status', '1') == '1' ? 'checked' : '' }}>
                                    <label for="active1">Active</label>
                                </div>
                                <div>
                                    <input type="radio" class="status-radio" id="inactive1" name="status"
                                        value="0" {{ old('status') == '0' ? 'checked' : '' }}>
                                    <label for="inactive1">Inactive</label>
                                </div>
                            </div>
                            @error('status')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Basic Info -->

            <div class="d-flex align-items-center justify-content-end">
                <a href="{{ route('users.index',['type' => $type]) }}" class="btn btn-light me-2">Cancel</a>
                <button type="submit" class="btn btn-primary">Create</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('script')
<script>
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
