@php
    $statusText = $status ?? '';
    $styles = \App\Support\EmailBrandTheme::badgeStyles($statusText);
@endphp
<span style="display:inline-block;padding:4px 10px;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:bold;line-height:1.4;{{ $styles }}">
    {{ $statusText }}
</span>
