<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Daily Raw Material Summary — {{ $summary['summary_date']->format('d.m.Y') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #1a1a1a; }
        h1 { font-size: 14px; margin: 0 0 2px; }
        .muted { color: #64748b; font-size: 8px; margin-bottom: 10px; }
        .header { margin-bottom: 12px; border-bottom: 2px solid #334155; padding-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #e2e8f0; font-weight: bold; text-align: left; padding: 3px 2px; border: 1px solid #cbd5e1; font-size: 7px; }
        td { padding: 2px; border: 1px solid #e2e8f0; font-size: 7px; }
        .text-right { text-align: right; }
        .footer-pending td { background: #fef3c7; font-weight: bold; }
        .footer-received td { background: #ecfdf5; font-weight: bold; }
        .footer-total td { background: #e2e8f0; font-weight: bold; }
        .formula-note { color: #64748b; font-size: 8px; margin-top: 8px; }
        .empty { color: #94a3b8; font-style: italic; padding: 6px 0; }
    </style>
</head>
<body>
    @include('dashboard.pdf.partials.rm_summary_content')
</body>
</html>
