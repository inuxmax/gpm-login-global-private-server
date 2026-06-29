<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>GPM Global Private Server v{{ \App\Services\SettingService::$server_version }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/admin.css', 'resources/js/admin/main.js'])
    <script>
        (function () {
            try {
                if (localStorage.getItem('admin_theme') === 'dark') {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
        window.__APP_CONFIG__ = {
            baseUrl: @json(url('/')),
            apiBaseUrl: @json(url('/admin/api')),
            apiV1BaseUrl: @json(url('/api')),
            legacyAdminUrl: @json(url('/admin')),
            logoutUrl: @json(url('/admin/auth/logout')),
            serverVersion: @json(\App\Services\SettingService::$server_version),
            csrfToken: @json(csrf_token()),
            initialUser: @json(auth()->user()),
        };
    </script>
</head>

<body>
    <div id="app"></div>
</body>

</html>
