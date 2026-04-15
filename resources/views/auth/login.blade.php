<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="author" content="Mayank Cattle Food PVT. LTD." />

    <title>Login | Mayank Cattle Food</title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.png') }}" />

    <!-- CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/tabler-icons/tabler-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
</head>

<body class="account-page">

<div class="main-wrapper">

    <div class="account-content">
        <div class="d-flex flex-wrap w-100 vh-100 overflow-hidden account-bg-01"
             style="background-image:url('{{ asset('assets/images/login-bg.jpg') }}');">

            <div class="d-flex align-items-center justify-content-center flex-wrap vh-100 overflow-auto p-4 w-100">

                <!-- Login Form -->
                <form method="POST" action="{{ route('login') }}" class="flex-fill">
                    @csrf

                    <div class="mx-auto mw-550 bg-backdrop">

                        <!-- Logo -->
                        <div class="text-center mb-4">
                            <img src="{{ asset('assets/images/logo.png') }}" class="img-fluid" alt="Logo">
                        </div>

                        <!-- Heading -->
                        <div class="mb-4">
                            <h4 class="mb-2 fs-20">Sign In</h4>
                            <p>Access the Nanogen panel using your email and passcode.</p>
                        </div>

                        <!-- Session Status -->
                        @if (session('status'))
                            <div class="alert alert-success">
                                {{ session('status') }}
                            </div>
                        @endif

                        <!-- Email -->
                        <div class="mb-3">
                            <label class="col-form-label">Email Address <span>*</span></label>
                            <div class="position-relative">
                                <span class="input-icon-addon">
                                    <i class="ti ti-mail"></i>
                                </span>
                                <input type="email"
                                       name="email"
                                       value="{{ old('email') }}"
                                       class="form-control @error('email') is-invalid @enderror"
                                       placeholder="Enter your email"
                                        autofocus>
                            </div>
                            @error('email')
                                <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label class="col-form-label">Password <span>*</span></label>
                            <div class="pass-group">
                                <input type="password"
                                       name="password"
                                       class="pass-input form-control @error('password') is-invalid @enderror"
                                       placeholder="Enter your password"
                                       >
                                <span class="ti toggle-password ti-eye-off"></span>
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Remember + Forgot -->
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="form-check form-check-md d-flex align-items-center">
                                <input class="form-check-input"
                                       type="checkbox"
                                       name="remember"
                                       id="remember_me">
                                <label class="form-check-label" for="remember_me">
                                    Remember Me
                                </label>
                            </div>

                            @if (Route::has('password.request'))
                                <a href="#{{-- route('password.request') --}}"
                                   class="text-primary fw-medium link-hover">
                                    Forgot Password?
                                </a>
                            @endif
                        </div>

                        <!-- Submit -->
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary w-100">
                                Sign In
                            </button>
                        </div>
                         <div class="mb-3">
                                <h6>New on our platform?<a href="{{ route('register') }}" class="text-purple link-hover"> Create
                                        an account</a></h6>
                            </div>

                        <!-- Footer -->
                        <div class="text-center">
                            <p class="fw-medium text-gray mb-0">
                                © {{ date('Y') }} Mayank Cattle Food Pvt. Ltd.
                            </p>
                        </div>

                    </div>
                </form>
                <!-- /Login Form -->

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
