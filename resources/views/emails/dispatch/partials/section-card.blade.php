@php
    $brand = $brand ?? \App\Support\EmailBrandTheme::colors();
@endphp
<table role="presentation" cellpadding="0" cellspacing="0" width="100%"
    style="border-collapse:collapse;margin-top:{{ $marginTop ?? '18px' }};background-color:{{ $brand['card_bg'] }}; border-radius: 10px; overflow: hidden;">
    <tr>
        <td style="padding:12px 16px;background-color: {{ $brand['primary'] }};font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:bold;color:#fff;">
            {{ $title }}
        </td>
    </tr>
    <tr>
        <td style="padding:0;">
            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
                {{ $slot }}
            </table>
        </td>
    </tr>
</table>
