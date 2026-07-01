@extends('emails.layouts.app')

@section('content')
    @php($brand = \App\Support\EmailBrandTheme::colors())
    <div data-block-id="mobile-otp" class="mceText"
         style="width:100%;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.5;color:{{ $brand['text_primary'] }};">

        <p style="margin:0 0 12px 0;">Dear {{ $user->name }},</p>

        <p style="margin:0 0 16px 0;">
            You requested to verify your identity on the <strong>Mayank Cattle Food</strong> mobile app.
            Use the One-Time Password (OTP) below to complete the login:
        </p>

        {{-- OTP display block --}}
        <p style="margin:0 0 20px 0;text-align:center;">
            <span style="
                display:inline-block;
                padding:12px 32px;
                background-color:{{ $brand['section_bg'] }};
                border:2px solid {{ $brand['primary'] }};
                border-radius:6px;
                font-size:28px;
                font-weight:bold;
                letter-spacing:10px;
                color:{{ $brand['primary'] }};
            ">{{ $otp }}</span>
        </p>

        <p style="margin:0 0 12px 0;color:{{ $brand['text_muted'] }};">
            This OTP is valid for <strong>{{ config('otp.expiry_minutes') }} minutes</strong>.
            Do not share it with anyone.
        </p>

        <p style="margin:0 0 16px 0;color:{{ $brand['text_muted'] }};">
            If you did not attempt to log in, please ignore this email. Your account remains secure.
        </p>

        <p style="margin:0;font-size:13px;color:{{ $brand['text_muted'] }};" class="last-child">
            Mayank Cattle Food
        </p>

    </div>
@endsection
