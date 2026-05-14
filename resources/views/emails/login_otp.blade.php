@extends('emails.layouts.app')
@section('content')
    <div data-block-id="3" class="mceText" id="dataBlockId-3" style="width:100%">
        <p>Dear {{ $user->name }},</p>
        <p><br></p>
        <p>We have received a request to verify your identity. To proceed, please use the following One-Time Password (OTP)
            for authentication:</p>
        <p><br></p>
        <p>OTP Code: <b>{{ $otp }}</b></p>
        <p><br></p>
        <p>Please do not share it with anyone for security reasons. If you didn't request this OTP, please disregard this
            message.</p>
        <p><br></p>
        <p class="last-child">Mayank Cattle Food</p>

    </div>
@endsection
