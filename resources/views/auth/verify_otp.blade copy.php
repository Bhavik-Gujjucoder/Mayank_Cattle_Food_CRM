{{-- @extends('layouts.app') --}}

{{-- @section('content') --}}
{{-- <div class=" loginsection " style="height: 80vh;">
            <img src="{{ asset('images/logo.svg') }}" alt="Pierro" title="logo" />
<span>Pierro</span>
<form method="POST" action="{{ route('verify.otp') }}" id="otpForm">
    @csrf
    <h3 class="text-center mb-4">Enter OTP</h3>

    @if (session('message'))
    <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    <div class="d-flex justify-content-center gap-2 mb-3">
        @for ($i = 0; $i < 6; $i++)
            <input type="text" class="otp form-control text-center" maxlength="1" inputmode="numeric" required
            style="width: 50px; height: 60px; font-size: 24px;">
            @endfor
    </div>

    @error('otp_combined')
    <div class="text-danger text-center mb-3">{{ $message }}</div>
    @enderror

    <input type="hidden" name="otp_combined" id="otp_combined">

    <button type="submit" class="btn btn-primary w-100">Verify OTP</button>
</form>
</div> --}}

<div class="loginsection">

    <div class="logregcon gc-login-section">
        <div class="">
            @if (session('message'))
            <div class="alert alert-success">{{ session('message') }}</div>
            @endif
            @if (session('error'))
            <div class="alert alert-danger text-center">{{ session('error') }}</div>
            @endif

            {{-- Top Logo Section --}}
            <div class="loginlogo">
                <div class="logo-cls">
                    {{-- <div class="login-screen">
                        <img src="{{ asset('images/logo.svg') }}" alt="Pierro" title="logo" />
                    </div> --}}
                    <div class="login-title">
                        <h1>Pierro</h1>
                        {{-- <h2>Productions</h2> --}}
                    </div>
                </div>



                <h5 class="mt-2">Please enter your credentials below.</h5>
            </div>

            {{-- OTP Form Box --}}
            <div class="loginform">
                <h6 class="mb-3">Please enter the OTP sent your registered email.</h6>
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
                            <button type="submit" class="btn btn-dark">Verify OTP</button>
                            <button type="submit" form="resendForm" class="btn btn-dark mx-2">Resend</button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('resend.otp') }}" id="resendForm" style="display: none;">
                        @csrf
                        {{-- <button type="submit" class="btn btn-dark mt-2">Resend</button> --}}
                    </form>
                </div>
            </div>

        </div>
    </div>

</div>
{{-- @endsection --}}

@section('scripts')
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
@endsection
