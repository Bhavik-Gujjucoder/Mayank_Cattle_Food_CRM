@extends('emails.layouts.app')
@section('content')
    @php($brand = \App\Support\EmailBrandTheme::colors())
    <div data-block-id="dispatch-payment-pending" class="mceText" style="width:100%;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.5;color:{{ $brand['text_primary'] }};">
        <p style="margin:0 0 12px 0;">Dear {{ $payload['dealer_name'] }},</p>
        <p style="margin:0 0 16px 0;">This is a reminder that payment is still pending on the dispatch below. A late payment fee has been accrued today. Please arrange payment at your earliest convenience.</p>

        @include('emails.dispatch.partials.payment-pending-callout')

        @include('emails.dispatch.partials.details')

        <p style="margin:20px 0 0 0;font-size:13px;color:{{ $brand['text_muted'] }};" class="last-child">
            Thank you,<br>Mayank Cattle Food
        </p>
    </div>
@endsection
