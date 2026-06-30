@php
    $brand = $brand ?? \App\Support\EmailBrandTheme::colors();
    $emphasize = $emphasize ?? false;
    $rowBg = $emphasize ? $brand['emphasize_bg'] : $brand['table_bg'];
    $valueWeight = $emphasize ? 'bold' : 'normal';
    $valueColor = $emphasize ? $brand['emphasize_text'] : $brand['text_primary'];
@endphp
<tr>
    <td width="38%" valign="top"
        style="padding:10px 16px;border-top:1px solid {{ $brand['border'] }};background-color:{{ $rowBg }};font-family:Arial,Helvetica,sans-serif;font-size:13px;color:{{ $brand['text_muted'] }};">
        <strong style="color:{{ $brand['text_primary'] }};">{{ $label }}</strong>
    </td>
    <td width="62%" valign="top"
        style="padding:10px 16px;border-top:1px solid {{ $brand['border'] }};background-color:{{ $rowBg }};font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:{{ $valueWeight }};color:{{ $valueColor }};">
        {!! $value !!}
    </td>
</tr>
