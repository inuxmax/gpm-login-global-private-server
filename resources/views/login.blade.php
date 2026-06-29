<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPM Global Private Server — Sign in</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            color-scheme: light;
            --background: oklch(0.992 0.003 240);
            --foreground: oklch(0.18 0.02 260);
            --card: oklch(1 0 0);
            --muted-foreground: oklch(0.48 0.015 250);
            --border: oklch(0.92 0.005 250);
            --brand: oklch(0.55 0.16 245);
            --brand-dark: oklch(0.22 0.04 260);
            --accent-dim: color-mix(in oklch, var(--brand) 18%, transparent);
            --accent-glow: color-mix(in oklch, var(--brand) 32%, transparent);
            --destructive: oklch(0.585 0.22 25);
            --radius: 0.625rem;
            --shadow-card: 0 4px 24px -8px oklch(0.25 0.04 260 / 10%);
            --body-gradient:
                radial-gradient(ellipse 120% 85% at 50% -30%, color-mix(in oklch, var(--brand) 10%, transparent), transparent 55%),
                radial-gradient(ellipse 90% 50% at 100% 0%, color-mix(in oklch, var(--brand) 8%, transparent), transparent 48%),
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

        .login-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: calc(var(--radius) * 1.6);
            box-shadow: var(--shadow-card);
            padding: 32px;
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--brand-dark), var(--brand));
            box-shadow: 0 6px 20px -4px var(--accent-glow);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
        }

        .brand-icon {
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
            box-shadow: 0 4px 14px -4px var(--accent-glow);
        }

        .brand-text .brand-title {
            font-size: 0.8125rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.2;
            color: var(--foreground);
        }

        .brand-text .brand-subtitle {
            font-size: 0.625rem;
            color: var(--muted-foreground);
            margin-top: 2px;
            font-weight: 500;
        }

        .form-title {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin: 0 0 6px;
            color: var(--foreground);
        }

        .form-subtitle {
            font-size: 0.8125rem;
            color: var(--muted-foreground);
            margin: 0 0 24px;
        }

        .field { margin-bottom: 16px; }

        .field label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--muted-foreground);
            margin-bottom: 6px;
        }

        .input {
            width: 100%;
            height: 40px;
            padding: 0 12px;
            font-size: 14px;
            font-family: inherit;
            color: var(--foreground);
            background: var(--background);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .input:hover { border-color: color-mix(in oklch, var(--brand) 25%, var(--border)); }

        .input:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px var(--accent-dim);
        }

        .password-wrap { position: relative; }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 8px;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: var(--muted-foreground);
            cursor: pointer;
            padding: 6px 8px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            font-family: inherit;
        }

        .password-toggle:hover {
            color: var(--brand);
            background: var(--accent-dim);
        }

        .submit-btn {
            width: 100%;
            height: 42px;
            margin-top: 8px;
            border: none;
            border-radius: var(--radius);
            background: var(--brand);
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: transform 0.05s ease, box-shadow 0.15s ease, opacity 0.15s ease;
            box-shadow: 0 4px 14px -4px var(--accent-glow);
        }

        .submit-btn:hover {
            background: color-mix(in oklch, var(--brand) 88%, black);
            box-shadow: 0 6px 20px -4px var(--accent-glow);
        }

        .submit-btn:active { transform: translateY(1px); }

        .alert {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 16px;
            padding: 10px 12px;
            background: color-mix(in oklch, var(--destructive) 12%, transparent);
            border: 1px solid color-mix(in oklch, var(--destructive) 35%, transparent);
            color: var(--destructive);
            border-radius: var(--radius);
            font-size: 13px;
            line-height: 1.4;
        }

        .alert-icon { flex-shrink: 0; margin-top: 1px; }

        .footnote {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: var(--muted-foreground);
        }
    </style>
</head>
<body>
    <div class="login-shell">
        <div class="login-card">
            <div class="brand">
                <div class="brand-icon">G</div>
                <div class="brand-text">
                    <div class="brand-title">GPM Global Private Server</div>
                    <div class="brand-subtitle">v{{ \App\Services\SettingService::$server_version }}</div>
                </div>
            </div>

            <h1 class="form-title">Sign in</h1>
            <p class="form-subtitle">Enter your admin credentials to continue.</p>

            @if (Session::has('error'))
                <div class="alert" role="alert">
                    <svg class="alert-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span>{{ Session::get('error') }}</span>
                </div>
            @endif

            <form method="post" autocomplete="on">
                @csrf

                <div class="field">
                    <label for="email">Username</label>
                    <input
                        id="email"
                        type="text"
                        name="email"
                        class="input"
                        autocomplete="username"
                        autofocus
                        required
                        placeholder="admin@example.com"
                    >
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="password-wrap">
                        <input
                            id="password"
                            type="password"
                            name="password"
                            class="input"
                            autocomplete="current-password"
                            required
                            placeholder="••••••••"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            Show
                        </button>
                    </div>
                </div>

                <button type="submit" class="submit-btn">Sign in</button>
            </form>

            <div class="footnote">
                © {{ date('Y') }} GPM Software Solutions
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            var input = document.getElementById('password');
            var btn = event.currentTarget;
            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = 'Hide';
            } else {
                input.type = 'password';
                btn.textContent = 'Show';
            }
        }
    </script>
</body>
</html>
