@php
    $brand = $brand ?? \App\Support\EmailBrandTheme::colors();
@endphp
<table role="presentation" cellpadding="0" cellspacing="0" width="100%"
    style="border-collapse:collapse;margin-top:{{ $marginTop ?? '18px' }};border:1px solid {{ $brand['border'] }};background-color:{{ $brand['card_bg'] }};">
    <tr>
        <td style="padding:12px 16px;background-color:{{ $brand['section_bg'] }};border-left:4px solid {{ $brand['primary'] }};font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:bold;color:{{ $brand['text_primary'] }};">
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
