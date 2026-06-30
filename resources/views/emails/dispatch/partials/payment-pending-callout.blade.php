@php($brand = $brand ?? \App\Support\EmailBrandTheme::colors())
@if (!empty($payload['late_fee_added_today']))
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%"
        style="border-collapse:separate;margin-top:8px;border:1px solid {{ $brand['callout_border'] }};background-color:{{ $brand['callout_bg'] }}; border-radius: 10px; overflow: hidden; margin-bottom: 18px;">
        <tr>
            <td style="padding:14px 16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:{{ $brand['text_primary'] }};">
                <strong style="display:block;margin-bottom:8px;font-size:15px;color:{{ $brand['primary'] }};">Payment Reminder</strong>
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
                    @if (!empty($payload['overdue_days']))
                        <tr>
                            <td width="42%" valign="top"
                                style="padding:4px 0;font-size:13px;color:{{ $brand['text_muted'] }};font-weight:bold;">
                                Days Overdue
                            </td>
                            <td width="58%" valign="top"
                                style="padding:4px 0;font-size:14px;color:{{ $brand['primary'] }};font-weight:bold;">
                                {{ $payload['overdue_days'] }} day{{ (int) $payload['overdue_days'] === 1 ? '' : 's' }}
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td width="42%" valign="top"
                            style="padding:4px 0;font-size:13px;color:{{ $brand['text_muted'] }};font-weight:bold;">
                            Late Fee Added Today
                        </td>
                        <td width="58%" valign="top"
                            style="padding:4px 0;font-size:14px;color:{{ $brand['primary'] }};font-weight:bold;">
                            {{ $payload['late_fee_added_today'] }}
                        </td>
                    </tr>
                    @if (!empty($payload['receivable']['balance_due']) && $payload['receivable']['balance_due'] !== '—')
                        <tr>
                            <td width="42%" valign="top"
                                style="padding:4px 0;font-size:13px;color:{{ $brand['text_muted'] }};font-weight:bold;">
                                Balance Due
                            </td>
                            <td width="58%" valign="top"
                                style="padding:4px 0;font-size:14px;color:{{ $brand['primary'] }};font-weight:bold;">
                                {{ $payload['receivable']['balance_due'] }}
                            </td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>
@endif
