<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Khởi tạo hệ thống — GPM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --background: oklch(0.992 0.003 240);
            --foreground: oklch(0.18 0.02 260);
            --card: oklch(1 0 0);
            --muted-foreground: oklch(0.48 0.015 250);
            --border: oklch(0.92 0.005 250);
            --brand: oklch(0.55 0.16 245);
            --destructive: oklch(0.585 0.22 25);
            --radius: 0.625rem;
            --shadow-card: 0 4px 24px -8px oklch(0.25 0.04 260 / 10%);
            --body-gradient:
                radial-gradient(ellipse 120% 85% at 50% -30%, color-mix(in oklch, var(--brand) 10%, transparent), transparent 55%),
                radial-gradient(ellipse 70% 45% at 0% 100%, color-mix(in oklch, var(--brand) 7%, transparent), transparent 45%);
            --font-sans: 'Inter', ui-sans-serif, system-ui, sans-serif;
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            font-family: var(--font-sans);
            color: var(--foreground);
            background: var(--background);
            background-image: var(--body-gradient);
            background-attachment: fixed;
            -webkit-font-smoothing: antialiased;
        }

        .setup-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
        }

        .setup-card {
            width: 100%;
            max-width: 640px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: calc(var(--radius) * 1.6);
            box-shadow: var(--shadow-card);
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .setup-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, oklch(0.22 0.04 260), var(--brand));
        }

        .setup-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1.5rem;
        }

        .setup-brand-icon {
            width: 2rem;
            height: 2rem;
            border-radius: 0.5rem;
            background: linear-gradient(135deg, var(--brand), color-mix(in oklch, var(--brand) 70%, oklch(0.55 0.14 245)));
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.875rem;
        }

        .setup-title {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin: 0 0 0.5rem;
        }

        .setup-subtitle {
            font-size: 0.875rem;
            color: var(--muted-foreground);
            margin: 0 0 1.25rem;
            line-height: 1.5;
        }

        .setup-alert {
            padding: 1rem;
            border-radius: var(--radius);
            background: color-mix(in oklch, var(--destructive) 10%, transparent);
            border: 1px solid color-mix(in oklch, var(--destructive) 30%, transparent);
            color: var(--destructive);
            font-size: 0.875rem;
        }

        .setup-alert pre {
            margin: 0.75rem 0 0;
            padding: 0.75rem;
            background: color-mix(in oklch, var(--destructive) 6%, var(--card));
            border-radius: calc(var(--radius) * 0.8);
            overflow-x: auto;
            font-size: 0.8125rem;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .setup-meta {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
            font-size: 0.8125rem;
            color: var(--muted-foreground);
        }

        .setup-meta code {
            background: color-mix(in oklch, var(--brand) 12%, transparent);
            color: var(--brand);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <div class="setup-shell">
        <div class="setup-card">
            <div class="setup-brand">
                <div class="setup-brand-icon">G</div>
                <div>
                    <div class="setup-title">Không thể kết nối database</div>
                    <div class="setup-subtitle">Vui lòng kiểm tra lại cấu hình trong file <code>.env</code> trên server.</div>
                </div>
            </div>

            @if(!empty($error ?? null))
                <div class="setup-alert">
                    <strong>Chi tiết lỗi:</strong>
                    <pre>{{ $error }}</pre>
                    <div class="setup-meta">
                        <div><strong>DB_HOST:</strong> <code>{{ env('DB_HOST', '(not set)') }}</code></div>
                        <div style="margin-top: 4px"><strong>DB_DATABASE:</strong> <code>{{ env('DB_DATABASE', '(not set)') }}</code></div>
                    </div>
                </div>
            @else
                <p class="setup-subtitle" style="margin: 0">Kết nối database thất bại. Vui lòng kiểm tra lại cấu hình tại file .env</p>
            @endif
        </div>
    </div>
</body>
</html>
