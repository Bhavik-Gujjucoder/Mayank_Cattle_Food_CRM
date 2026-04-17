<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="utf-8" />
   <meta http-equiv="X-UA-Compatible" content="IE=edge" />
   <meta name="viewport" content="width=device-width, initial-scale=1" />
   <meta name="author" content="Mayank Cattle Food PVT. LTD." />

   <title>Reset Password | Mayank Cattle Food PVT. LTD.</title>

   <!-- Favicon -->
   <link rel="icon" href="{{ asset('assets/images/favicon.png') }}" type="image/x-icon">

   <!-- CSS -->
   <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
   <link rel="stylesheet" href="{{ asset('assets/plugins/tabler-icons/tabler-icons.css') }}">
   <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/all.min.css') }}">
   <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
</head>

<body class="account-page">

<div class="main-wrapper">
    <div class="account-content">
        <div class="d-flex flex-wrap w-100 vh-100 overflow-hidden account-bg-04 accountmainbg"
             style="background-image:url('{{ asset('assets/images/reset-bg.jpg') }}'); background-size: cover; background-position: center;">

            <div class="d-flex align-items-center justify-content-center flex-wrap vh-100 overflow-auto p-4 w-100">

                <form method="POST" action="{{ route('password.store') }}" class="flex-fill">
                    @csrf

                    <input type="hidden" name="token" value="{{ request()->route('token') }}">

                    <div class="mx-auto mw-550 bg-backdrop">
                        <div class="text-center mb-4">
                            {{-- <img src="{{ asset('assets/images/logo.png') }}" class="img-fluid" alt="Logo"> --}}
                            <img src="{{ asset('storage/company_logo/' . getSetting('company_logo')) }}" class="img-fluid" alt="Logo">
                        </div>

                        <div class="mb-4">
                            <h4 class="mb-2 fs-20">Reset Password</h4>
                            <p>Enter New Password & Confirm Password to get inside</p>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label class="col-form-label">Email</label>
                            <input type="email" name="email"
                                   value="{{ old('email', request()->email) }}"
                                   class="form-control" required>

                            @error('email')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label class="col-form-label">Password</label>
                            <div class="pass-group">
                                <input type="password" name="password" class="form-control" required>
                                <span class="ti toggle-password ti-eye-off"></span>
                            </div>

                            @error('password')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-3">
                            <label class="col-form-label">Confirm Password</label>
                            <div class="pass-group">
                                <input type="password" name="password_confirmation" class="form-control" required>
                                <span class="ti toggle-password ti-eye-off"></span>
                            </div>

                            @error('password_confirmation')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary w-100">
                                Change Password
                            </button>
                        </div>

                        <div class="mb-3 text-center">
                            <h6>
                                Return to
                                <a href="{{ route('login') }}" class="text-purple link-hover">Login</a>
                            </h6>
                        </div>

                        <div class="text-center">
                            <p class="fw-medium text-gray">
                                {{-- Copyright © 2026 - Mayank Cattle Food Private Limited --}}
                                  {{ getSetting('copyright_msg') }}
                            </p>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<!-- JS -->
<script src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/script.js') }}"></script>
<script>
$(document).ready(function () {

    // Password toggle
    $(document).on('click', '.toggle-password', function () {
        let input = $(this).closest('.pass-group').find('input');

        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            $(this).removeClass('ti-eye-off').addClass('ti-eye');
        } else {
            input.attr('type', 'password');
            $(this).removeClass('ti-eye').addClass('ti-eye-off');
        }
    });

});
</script>
</body>
</html>

