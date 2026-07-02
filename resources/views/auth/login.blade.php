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
                                <img src="{{ asset('storage/company_logo/' . getSetting('company_logo')) }}"
                                    class="img-fluid" alt="Logo">
                            </div>

                            <!-- Heading -->
                            <div class="mb-4">
                                <h4 class="mb-2 fs-20">Sign In</h4>
                                <p>Access the Mayank Cattle Food panel using your email and passcode.</p>
                            </div>

                            <!-- Session Status -->
                            @if (session('status'))
                                <div class="alert alert-success">{{ session('status') }}</div>
                            @endif

                            {{-- ── Credential field: accepts Email OR Mobile Number ──────── --}}
                            <div class="mb-3">
                                <label class="col-form-label">
                                    Email / Mobile Number <span>*</span>
                                </label>
                                <div class="position-relative">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-mail" id="credential-icon"></i>
                                    </span>
                                    <input type="text"
                                           name="email"
                                           id="credential-input"
                                           value="{{ old('email') }}"
                                           class="form-control @error('email') is-invalid @enderror"
                                           placeholder="Enter email or mobile number"
                                           autocomplete="username"
                                           autofocus>
                                </div>
                                @error('email')
                                    <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                                @enderror
                                {{-- Contextual hint — updated dynamically by JS --}}
                                <small id="credential-hint" class="text-muted d-block mt-1"></small>
                            </div>

                            {{-- ── Password ─────────────────────────────────────────────── --}}
                            {{-- Always visible. Email flow: OTP after password check.
                                 Phone flow: password is verified against the dealer's account. --}}
                            <div class="mb-3">
                                <label class="col-form-label">Password <span>*</span></label>
                                <div class="pass-group">
                                    <input type="password"
                                           name="password"
                                           id="password-input"
                                           class="pass-input form-control @error('password') is-invalid @enderror"
                                           placeholder="Enter your password"
                                           autocomplete="current-password">
                                    <span class="ti toggle-password ti-eye-off"></span>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Remember Me + Forgot Password -->
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="form-check form-check-md d-flex align-items-center">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember_me">
                                    <label class="form-check-label" for="remember_me">Remember Me</label>
                                </div>

                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="text-primary fw-medium link-hover">
                                        Forgot Password?
                                    </a>
                                @endif
                            </div>

                            <!-- Submit -->
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary w-100">Sign In</button>
                            </div>

                            <!-- Footer -->
                            <div class="text-center">
                                <p class="fw-medium text-gray mb-0">{{ getSetting('copyright_msg') }}</p>
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

    <script>
        /*
         * Detect whether the credential field holds an e-mail or a phone number
         * and show a contextual hint + swap the field icon.
         *
         * Password field is always visible — both login modes require it.
         *
         *   Email  -> icon: ti-mail    | hint: "OTP will be sent to your email"
         *   Phone  -> icon: ti-device-mobile | hint: "Enter your password to login (Dealers only)"
         */
        (function ($) {

            var $input = $('#credential-input');
            var $icon  = $('#credential-icon');
            var $hint  = $('#credential-hint');

            function isPhoneNumber(val) {
                // Pure digits, 10 to 15 characters
                return /^[0-9]{10,15}$/.test(val);
            }

            function isEmailAddress(val) {
                return val.indexOf('@') !== -1;
            }

            function evaluateCredential() {
                var val = $input.val().trim();

                if (isPhoneNumber(val)) {
                    $icon.removeClass('ti-mail').addClass('ti-device-mobile');
                    $hint.text('Mobile number login is available for Dealers only. Password is required.');
                } else if (isEmailAddress(val)) {
                    $icon.removeClass('ti-device-mobile').addClass('ti-mail');
                    $hint.text('OTP will be sent to your registered email after verification.');
                } else {
                    $icon.removeClass('ti-device-mobile').addClass('ti-mail');
                    $hint.text('');
                }
            }

            // Evaluate on every keystroke
            $input.on('input', evaluateCredential);

            // Run on page load — handles old() repopulation after a server validation error
            evaluateCredential();

        }(jQuery));
    </script>

</body>

</html>
