@extends('emails.layouts.app')
@section('content')
    @php($brand = \App\Support\EmailBrandTheme::colors())
    <div data-block-id="backup-created" class="mceText" style="width:100%;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.5;color:{{ $brand['text_primary'] }};">
        <p style="margin:0 0 12px 0;">Hello,</p>
        <p style="margin:0 0 20px 0;">A new encrypted system backup has been created. The backup file is attached to this email.</p>

        <table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:0 0 20px 0;">
            <tr>
                <td style="padding:8px 12px;border:1px solid {{ $brand['border'] }};background:{{ $brand['section_bg'] }};font-weight:bold;width:40%;">Filename</td>
                <td style="padding:8px 12px;border:1px solid {{ $brand['border'] }};">{{ $payload['filename'] }}</td>
            </tr>
            <tr>
                <td style="padding:8px 12px;border:1px solid {{ $brand['border'] }};background:{{ $brand['section_bg'] }};font-weight:bold;">Size</td>
                <td style="padding:8px 12px;border:1px solid {{ $brand['border'] }};">{{ $payload['size_label'] }}</td>
            </tr>
            <tr>
                <td style="padding:8px 12px;border:1px solid {{ $brand['border'] }};background:{{ $brand['section_bg'] }};font-weight:bold;">Backup Passphrase</td>
                <td style="padding:8px 12px;border:1px solid {{ $brand['border'] }};">{{ $payload['passphrase'] }}</td>
            </tr>
            <tr>
                <td style="padding:8px 12px;border:1px solid {{ $brand['border'] }};background:{{ $brand['section_bg'] }};font-weight:bold;">Created At</td>
                <td style="padding:8px 12px;border:1px solid {{ $brand['border'] }};">{{ $payload['created_at'] }}</td>
            </tr>
            <tr>
                <td style="padding:8px 12px;border:1px solid {{ $brand['border'] }};background:{{ $brand['section_bg'] }};font-weight:bold;">Created By</td>
                <td style="padding:8px 12px;border:1px solid {{ $brand['border'] }};">{{ $payload['created_by_name'] }}</td>
            </tr>
        </table>

        <p style="margin:0 0 12px 0;font-size:13px;color:{{ $brand['text_muted'] }};">
            Store the backup passphrase securely. The encrypted backup cannot be restored without it.
        </p>

        <p style="margin:20px 0 0 0;font-size:13px;color:{{ $brand['text_muted'] }};" class="last-child">
            Thank you,<br>Mayank Cattle Food
        </p>
    </div>
@endsection
