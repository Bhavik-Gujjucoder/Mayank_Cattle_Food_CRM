<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Daily Report — {{ now()->format('d.m.Y') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1a1a1a; }
        h1 { font-size: 14px; margin: 0 0 2px; }
        h3 { font-size: 11px; margin: 0 0 6px; color: #1e3a8a; background: #dbeafe; padding: 5px 8px; }
        .muted { color: #64748b; font-size: 9px; margin-bottom: 10px; }
        .header { margin-bottom: 12px; padding-bottom: 8px; }
        .rm-header { border-bottom: 2px solid #334155; }
        .sales-header { border-bottom: 2px solid #1e3a8a; }
        .section { page-break-before: always; }
        .section-first { page-break-before: auto; }
        .group { margin-bottom: 14px; page-break-inside: avoid; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .rm-table th { background: #e2e8f0; font-weight: bold; text-align: left; padding: 3px 2px; border: 1px solid #cbd5e1; font-size: 7px; }
        .rm-table td { padding: 2px; border: 1px solid #e2e8f0; font-size: 7px; }
        .sales-table th { background: #f0f4fa; font-weight: bold; text-align: left; padding: 5px 6px; border: 1px solid #cbd5e1; font-size: 9px; color: #3e6eaf; }
        .sales-table td { padding: 4px 6px; border: 1px solid #e2e8f0; font-size: 9px; }
        .text-right { text-align: right; }
        .footer-pending td { background: #fef3c7; font-weight: bold; }
        .footer-received td { background: #ecfdf5; font-weight: bold; }
        .footer-total td { background: #e2e8f0; font-weight: bold; }
        .total-row td { background: #fef3c7; font-weight: bold; }
        .empty { color: #94a3b8; font-style: italic; padding: 6px 0; }
        .formula-note { color: #64748b; font-size: 8px; margin-top: 8px; }
    </style>
</head>
<body>
    @if ($summary)
        @include('dashboard.pdf.partials.rm_summary_content')
    @endif

    @if ($payload)
        @include('dashboard.pdf.partials.sales_sheets_content', [
            'salesStartNewPage' => (bool) $summary,
        ])
    @endif
</body>
</html>
