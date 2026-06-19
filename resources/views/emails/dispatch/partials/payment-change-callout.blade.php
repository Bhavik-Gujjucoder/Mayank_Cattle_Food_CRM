@php($brand = $brand ?? \App\Support\EmailBrandTheme::colors())
@if (!empty($payload['previous_payment_status']))
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%"
        style="border-collapse:collapse;margin-top:8px;border:1px solid {{ $brand['callout_border'] }};background-color:{{ $brand['callout_bg'] }};">
        <tr>
            <td style="padding:14px 16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:{{ $brand['text_primary'] }};">
                <strong style="display:block;margin-bottom:8px;font-size:15px;color:{{ $brand['primary'] }};">Payment Update</strong>
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
                    <tr>
                        <td width="42%" valign="top"
                            style="padding:4px 0;font-size:13px;color:{{ $brand['text_muted'] }};font-weight:bold;">
                            Previous Status
                        </td>
                        <td width="58%" valign="top" style="padding:4px 0;">
                            @include('emails.dispatch.partials.status-badge', [
                                'status' => $payload['previous_payment_status'],
                            ])
                        </td>
                    </tr>
                    @if (!empty($payload['previous_partial_paid_amount']))
                        <tr>
                            <td width="42%" valign="top"
                                style="padding:4px 0;font-size:13px;color:{{ $brand['text_muted'] }};font-weight:bold;">
                                Previous Paid Amount
                            </td>
                            <td width="58%" valign="top"
                                style="padding:4px 0;font-size:14px;color:{{ $brand['primary'] }};font-weight:bold;">
                                {{ $payload['previous_partial_paid_amount'] }}
                            </td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>
@endif
