@extends('emails.layouts.app')
@section('content')
    @php($brand = \App\Support\EmailBrandTheme::colors())
    <div data-block-id="backup-created" class="mceText" style="width:100%;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.5;color:{{ $brand['text_primary'] }};">
        <p style="margin:0 0 12px 0;">Hello,</p>
        <p style="margin:0 0 20px 0;">A new encrypted system backup has been created. The backup file is attached to this email.</p>

        <table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:0 0 20px 0; border-radius: 10px; overflow: hidden;">
            <tbody>
                <tr>
                    <td style="padding:8px 12px;background:{{ $brand['primary'] }}; color: #fff; font-weight:bold;width:40%;">Filename</td>
                    <td style="padding:8px 12px; background:{{ $brand['table_bg'] }};">{{ $payload['filename'] }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 12px;border-top:1px solid {{ $brand['border'] }};background:{{ $brand['primary'] }}; color: #fff; font-weight:bold;">Size</td>
                    <td style="padding:8px 12px;border-top:1px solid {{ $brand['border'] }}; background:{{ $brand['table_bg'] }};">{{ $payload['size_label'] }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 12px;border-top:1px solid {{ $brand['border'] }};background:{{ $brand['primary'] }}; color: #fff; font-weight:bold;">Backup Passphrase</td>
                    <td style="padding:8px 12px;border-top:1px solid {{ $brand['border'] }}; background:{{ $brand['table_bg'] }};">{{ $payload['passphrase'] }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 12px;border-top:1px solid {{ $brand['border'] }};background:{{ $brand['primary'] }}; color: #fff; font-weight:bold;">Created At</td>
                    <td style="padding:8px 12px;border-top:1px solid {{ $brand['border'] }}; background:{{ $brand['table_bg'] }};">{{ $payload['created_at'] }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 12px;border-top:1px solid {{ $brand['border'] }};background:{{ $brand['primary'] }}; color: #fff; font-weight:bold;">Created By</td>
                    <td style="padding:8px 12px;border-top:1px solid {{ $brand['border'] }}; background:{{ $brand['table_bg'] }};">{{ $payload['created_by_name'] }}</td>
                </tr>
            </tbody>
        </table>

        <p style="margin:0 0 12px 0;font-size:13px;color:{{ $brand['text_muted'] }};">
            Store the backup passphrase securely. The encrypted backup cannot be restored without it.
        </p>

        <p style="margin:20px 0 0 0;font-size:13px;color:{{ $brand['text_muted'] }};" class="last-child">
            Thank you,<br>Mayank Cattle Food
        </p>
    </div>
@endsection
