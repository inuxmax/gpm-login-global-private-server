<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPM Global Private server — Sign in</title>

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
            --danger-bg: #fef2f2;
            --danger-border: #fecaca;
            --danger-text: #b91c1c;
            --input-border: #d1d5db;
            --input-focus: #60a5fa;
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
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08), 0 1px 2px rgba(0, 0, 0, 0.04);
            padding: 32px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
        }

        .brand-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(180deg, var(--primary-dark) 0%, var(--primary) 100%);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 20px;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 10px rgba(30, 64, 175, 0.25);
        }

        .brand-text .brand-title {
            font-size: 16px;
            font-weight: 700;
            line-height: 1.2;
            color: var(--text-primary);
        }

        .brand-text .brand-subtitle {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .form-title {
            font-size: 20px;
            font-weight: 600;
            margin: 0 0 6px;
            color: var(--text-primary);
        }

        .form-subtitle {
            font-size: 13px;
            color: var(--text-secondary);
            margin: 0 0 24px;
        }

        .field {
            margin-bottom: 16px;
        }

        .field label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 6px;
        }

        .input {
            width: 100%;
            height: 40px;
            padding: 0 12px;
            font-size: 14px;
            color: var(--text-primary);
            background: #fff;
            border: 1px solid var(--input-border);
            border-radius: 8px;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .input:hover {
            border-color: #9ca3af;
        }

        .input:focus {
            border-color: var(--input-focus);
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.18);
        }

        .password-wrap {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 8px;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: var(--text-muted);
            cursor: pointer;
            padding: 6px 8px;
            font-size: 12px;
            font-weight: 500;
            border-radius: 6px;
        }

        .password-toggle:hover {
            color: var(--primary);
            background: rgba(96, 165, 250, 0.1);
        }

        .submit-btn {
            width: 100%;
            height: 42px;
            margin-top: 8px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.2px;
            cursor: pointer;
            transition: transform 0.05s ease, box-shadow 0.15s ease, opacity 0.15s ease;
            box-shadow: 0 4px 10px rgba(30, 64, 175, 0.25);
        }

        .submit-btn:hover {
            box-shadow: 0 6px 14px rgba(30, 64, 175, 0.35);
        }

        .submit-btn:active {
            transform: translateY(1px);
        }

        .alert {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 16px;
            padding: 10px 12px;
            background: var(--danger-bg);
            border: 1px solid var(--danger-border);
            color: var(--danger-text);
            border-radius: 8px;
            font-size: 13px;
            line-height: 1.4;
        }

        .alert-icon {
            flex-shrink: 0;
            margin-top: 1px;
        }

        .footnote {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: var(--text-muted);
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
