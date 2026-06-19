@extends('emails.layouts.app')
@section('content')
    @php($brand = \App\Support\EmailBrandTheme::colors())
    <div data-block-id="3" class="mceText" id="dataBlockId-3" style="width:100%;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.5;color:{{ $brand['text_primary'] }};">
        <p style="margin:0 0 12px 0;">Dear {{ $user->name }},</p>
        <p style="margin:0 0 16px 0;">We have received a request to verify your identity. To proceed, please use the following One-Time Password (OTP) for authentication:</p>
        <p style="margin:0 0 16px 0;">
            OTP Code: <strong style="color:{{ $brand['primary'] }};font-size:18px;">{{ $otp }}</strong>
        </p>
        <p style="margin:0 0 16px 0;color:{{ $brand['text_muted'] }};">Please do not share it with anyone for security reasons. If you didn't request this OTP, please disregard this message.</p>
        <p style="margin:0;font-size:13px;color:{{ $brand['text_muted'] }};" class="last-child">Mayank Cattle Food</p>
    </div>
@endsection
