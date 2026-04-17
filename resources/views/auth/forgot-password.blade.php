<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="author" content="Mayank Cattle Food PVT. LTD. " />

    <title>Mayank Cattle Food</title>

    <link rel="icon" href="{{ asset('assets/images/favicon.png') }}" type="image/x-icon">

    <!-- CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/tabler-icons/tabler-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
</head>

<body class="account-page">

<div class="main-wrapper">
    <div class="account-content">
        <div class="d-flex flex-wrap w-100 vh-100 overflow-hidden account-bg-03 accountmainbg"
             style="background-image:url({{ asset('assets/images/forgot-bg.jpg') }});">

            <div class="d-flex align-items-center justify-content-center flex-wrap vh-100 overflow-auto p-4 w-100">

                <form method="POST" action="{{ route('password.email') }}" class="flex-fill">
                    @csrf

                    <div class="mx-auto mw-550 bg-backdrop">

                        <!-- Logo -->
                        <div class="text-center mb-4">
                            {{-- <img src="{{ asset('assets/images/logo.png') }}" class="img-fluid" alt="Logo"> --}}
                            <img src="{{ asset('storage/company_logo/' . getSetting('company_logo')) }}" class="img-fluid" alt="Logo">
                        </div>

                        <!-- Title -->
                        <div class="mb-4">
                            <h4 class="mb-2 fs-20">Forgot Password?</h4>
                            <p>If you forgot your password, well, then we’ll email you instructions to reset your password.</p>
                        </div>

                        <!-- Session Status -->
                        @if (session('status'))
                            <div class="alert alert-success">
                                {{ session('status') }}
                            </div>
                        @endif

                        <!-- Email -->
                        <div class="mb-3">
                            <label class="col-form-label">Email Address</label>
                            <div class="position-relative">
                                <span class="input-icon-addon">
                                    <i class="ti ti-mail"></i>
                                </span>
                                <input type="email" name="email"
                                       value="{{ old('email') }}" placeholder="Enter Email Address"
                                       class="form-control @error('email') is-invalid @enderror"
                                       required autofocus>

                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary w-100">
                                Email Password Reset Link
                            </button>
                        </div>

                        <!-- Back to login -->
                        <div class="mb-3 text-center">
                            <h6>
                                Return to
                                <a href="{{ route('login') }}" class="text-purple link-hover">Login</a>
                            </h6>
                        </div>

                        <!-- Footer -->
                        <div class="text-center">
                            <p class="fw-medium text-gray">
                                {{-- Copyright &copy; {{ date('Y') }} - Mayank Cattle Food Private Limited --}}
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
<script src="{{ asset('assets/js/feather.min.js') }}"></script>
<script src="{{ asset('assets/js/jquery.slimscroll.min.js') }}"></script>
<script src="{{ asset('assets/js/script.js') }}"></script>

</body>
</html>
