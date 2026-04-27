<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPM Global Private Server v{{ \App\Services\SettingService::$server_version }}</title>

    <style>
        :root {
            --app-bg: #f5f7fa;
            --card-bg: #ffffff;
            --card-border: #e5e7eb;
            --text-primary: #111827;
            --text-secondary: #4b5563;
            --text-muted: #9ca3af;
            --primary: #1e40af;
            --primary-dark: #1e3a8a;
            --primary-light: #60a5fa;
            --success: #10b981;
            --success-bg: #ecfdf5;
            --success-border: #a7f3d0;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue',
                Arial, 'Noto Sans', sans-serif;
            color: var(--text-primary);
            background:
                radial-gradient(ellipse at top left, rgba(96, 165, 250, 0.18), transparent 55%),
                radial-gradient(ellipse at bottom right, rgba(30, 64, 175, 0.15), transparent 60%),
                var(--app-bg);
        }

        .ready-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
        }

        .ready-card {
            width: 100%;
            max-width: 480px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08), 0 1px 2px rgba(0, 0, 0, 0.04);
            padding: 36px 32px 28px;
            text-align: center;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
        }

        .brand-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(180deg, var(--primary-dark) 0%, var(--primary) 100%);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            box-shadow: 0 4px 10px rgba(30, 64, 175, 0.25);
        }

        .brand-text {
            text-align: left;
        }

        .brand-title {
            font-size: 15px;
            font-weight: 700;
            line-height: 1.2;
            color: var(--text-primary);
        }

        .brand-version {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 999px;
            background: var(--success-bg);
            border: 1px solid var(--success-border);
            color: var(--success);
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 18px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--success);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.18);
            animation: pulse 1.6s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.18); }
            50% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0.05); }
        }

        .ready-image {
            display: block;
            margin: 0 auto 18px;
            max-width: 240px;
            height: auto;
        }

        .title {
            font-size: 22px;
            font-weight: 700;
            margin: 0 0 6px;
            color: var(--text-primary);
        }

        .subtitle {
            font-size: 14px;
            color: var(--text-secondary);
            margin: 0 0 24px;
            line-height: 1.5;
        }

        .cta-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-width: 220px;
            height: 44px;
            padding: 0 20px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.05s ease, box-shadow 0.15s ease;
            box-shadow: 0 4px 10px rgba(30, 64, 175, 0.25);
        }

        .cta-btn:hover {
            box-shadow: 0 6px 14px rgba(30, 64, 175, 0.35);
        }

        .cta-btn:active {
            transform: translateY(1px);
        }

        .footnote {
            margin-top: 22px;
            font-size: 12px;
            color: var(--text-muted);
        }
    </style>
</head>
<body>
    @php
        $adminUrl = url('admin/app/system');
        $version = \App\Services\SettingService::$server_version;
    @endphp

    <div class="ready-shell">
        <div class="ready-card">
            <div class="brand">
                <div class="brand-icon">G</div>
                <div class="brand-text">
                    <div class="brand-title">GPM Global Private Server</div>
                    <div class="brand-version">v{{ $version }}</div>
                </div>
            </div>

            <img src="assets/img/running.png" alt="" class="ready-image">

            <a href="{{ $adminUrl }}" class="cta-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14"></path>
                    <path d="M12 5l7 7-7 7"></path>
                </svg>
                Go to admin console
            </a>

            <div class="footnote">
                © {{ date('Y') }} GPM Global Private Server
            </div>
        </div>
    </div>
</body>
</html>
