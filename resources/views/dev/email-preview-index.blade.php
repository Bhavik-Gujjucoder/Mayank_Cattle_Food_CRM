<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Previews (Local)</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 32px 20px;
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f5f5;
            color: #222;
        }
        .wrap {
            max-width: 720px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 28px 32px;
        }
        h1 {
            margin: 0 0 8px;
            font-size: 24px;
        }
        p {
            margin: 0 0 20px;
            color: #666;
            line-height: 1.5;
        }
        ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        li + li { margin-top: 10px; }
        a {
            display: block;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-decoration: none;
            color: #8b6914;
            background: #fffce3;
            font-weight: 600;
        }
        a:hover { background: #fff6c8; }
        .note {
            margin-top: 24px;
            padding: 12px 14px;
            background: #fff8e1;
            border-left: 4px solid #c9a227;
            font-size: 13px;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>Email Template Previews</h1>
        <p>Local development only. Open any template to preview its HTML in the browser. Refresh after editing Blade files.</p>

        <ul>
            @foreach ($previews as $preview)
                <li>
                    <a href="{{ $preview['route'] }}" target="_blank" rel="noopener">
                        {{ $preview['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="note">
            Tip: resize the browser window to ~660px width to approximate how the email looks in most clients.
        </div>
    </div>
</body>
</html>
