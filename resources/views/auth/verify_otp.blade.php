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

    <style>
        /* ── OTP input row ────────────────────────────────────────── */
        .otp-cls {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 16px;
        }

        .otp-cls .otp {
            width: 48px;
            height: 52px;
            flex: 0 0 48px;
            /* prevent flex from stretching/shrinking */
            padding: 0;
            font-size: 22px;
            font-weight: 600;
            text-align: center;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            background-color: #f9fafb;
            color: #111827;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            /* override form-control's min-height so the box stays square */
            min-height: unset;
        }

        .otp-cls .otp:focus {
            border-color: #2159a3;
            box-shadow: 0 0 0 3px rgba(33, 89, 163, 0.15);
            background-color: #fff;
        }

        .otp-cls .otp:not(:placeholder-shown) {
            border-color: #2159a3;
            background-color: #fff;
        }
    </style>
</head>

<body class="account-page">

    <div class="main-wrapper">

        <div class="account-content">
            <div class="d-flex flex-wrap w-100 vh-100 overflow-hidden account-bg-01"
                style="background-image:url('{{ asset('assets/images/login-bg.jpg') }}');">

                <div class="d-flex align-items-center justify-content-center flex-wrap vh-100 overflow-auto p-4 w-100">

                    <!-- Login Form -->
                    {{-- <form method="POST" action="{{ route('login') }}" class="flex-fill">
                        @csrf --}}
                    <div class="mx-auto mw-550 bg-backdrop">

                        <!-- Logo -->
                        <div class="text-center mb-4">
                            {{-- <img src="{{ asset('assets/images/logo.png') }}" class="img-fluid" alt="Logo"> --}}
                            <img src="{{ asset('storage/company_logo/' . getSetting('company_logo')) }}"
                                class="img-fluid" alt="Logo">
                        </div>

                        @if (session('message'))
                            <div class="alert alert-success">{{ session('message') }}</div>
                        @endif
                        @if (session('error'))
                            <div class="alert alert-danger text-center">{{ session('error') }}</div>
                        @endif

                        <!-- Heading -->
                        <div class="mb-4">
                            {{-- <h4 class="mb-2 fs-20">Sign In</h4> --}}
                            <p>Please enter your credentials below.</p>
                        </div>

                        <!-- Session Status -->
                        @if (session('status'))
                            <div class="alert alert-success">
                                {{ session('status') }}
                            </div>
                        @endif

                        <div class="loginform">
                            <h6 class="mb-3">Please enter the OTP sent to your registered email. If you do not see it yet, wait a moment or use Resend.</h6>
                            <div class="">
                                <form method="POST" action="{{ route('verify.otp') }}" id="otpForm">
                                    @csrf
                                    <div class="otp-cls">
                                        @for ($i = 0; $i < 6; $i++)
                                            <input type="text" class="otp form-control text-center" maxlength="1"
                                                inputmode="numeric" required>
                                        @endfor
                                    </div>
                                    @error('otp_combined')
                                        <div class="text-danger text-center mb-3">{{ $message }}</div>
                                    @enderror
                                    <input type="hidden" name="otp_combined" id="otp_combined">
                                    {{-- <button type="submit" class="btn btn-dark ">Verify OTP</button> --}}

                                    <div class="d-flex gap-2 align-items-center mt-3 justify-content-center">
                                        <button type="submit" class="btn btn-primary w-100">Verify OTP</button>
                                        <button type="submit" form="resendForm"
                                            class="btn btn-primary w-100 mx-2">Resend</button>
                                    </div>
                                </form>

                                <form method="POST" action="{{ route('resend.otp') }}" id="resendForm"
                                    style="display: none;">
                                    @csrf
                                    {{-- <button type="submit" class="btn btn-dark mt-2">Resend</button> --}}
                                </form>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="text-center mt-3">
                            <p class="fw-medium text-gray mb-0">
                                {{-- © {{ date('Y') }} Mayank Cattle Food Pvt. Ltd. --}}
                                {{ getSetting('copyright_msg') }}
                            </p>
                        </div>

                    </div>
                    {{-- </form> --}}

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
        const inputs = document.querySelectorAll('.otp');
        inputs.forEach((el, i) => {
            el.addEventListener('input', e => {
                if (el.value.length === 1 && i < inputs.length - 1) inputs[i + 1].focus();
            });
            el.addEventListener('keydown', e => {
                if (e.key === 'Backspace' && !el.value && i > 0) inputs[i - 1].focus();
            });
            el.addEventListener('paste', e => {
                const data = e.clipboardData.getData('text').slice(0, 6);
                data.split('').forEach((char, idx) => inputs[idx] && (inputs[idx].value = char));
                inputs[Math.min(data.length, 5)].focus();
                e.preventDefault();
            });
        });

        document.getElementById('otpForm').addEventListener('submit', () => {
            document.getElementById('otp_combined').value = Array.from(inputs).map(i => i.value).join('');
        });
    </script>

</body>

</html>
