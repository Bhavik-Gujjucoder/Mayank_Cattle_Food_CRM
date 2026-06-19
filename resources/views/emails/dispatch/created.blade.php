@extends('emails.layouts.app')
@section('content')
    @php($brand = \App\Support\EmailBrandTheme::colors())
    <div data-block-id="dispatch-created" class="mceText" style="width:100%;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.5;color:{{ $brand['text_primary'] }};">
        <p style="margin:0 0 12px 0;">Dear {{ $payload['dealer_name'] }},</p>
        <p style="margin:0 0 20px 0;">A new dispatch has been recorded against your order. Please find the details below.</p>

        @include('emails.dispatch.partials.details')

        <p style="margin:20px 0 0 0;font-size:13px;color:{{ $brand['text_muted'] }};" class="last-child">
            Thank you,<br>Mayank Cattle Food
        </p>
    </div>
@endsection
