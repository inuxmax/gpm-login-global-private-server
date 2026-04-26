<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Khởi tạo hệ thống</title>

    <link rel="stylesheet" href="{{asset('assets/css/setup.css')}}">
    <link rel="stylesheet" href="{{asset('assets/plugins/bootstrap-5.1.3-dist/css/bootstrap.min.css')}}">
    <link rel="stylesheet" href="{{asset('assets/plugins/fontawesome-free-5.15.4-web/css/all.min.css')}}">

</head>
<body>
    <div id="app">
        <div class="center-box">
            <h1>Kết nối database thất bại. Vui lòng kiểm tra lại cấu hình tại file .env</h1>
            @if(!empty($error ?? null))
                <div class="alert alert-danger mt-3">
                    <strong>Chi tiết lỗi:</strong>
                    <pre class="mb-0">{{ $error }}</pre>
                    <hr class="my-2">
                    <div><strong>DB_HOST:</strong> <code>{{ env('DB_HOST', '(not set)') }}</code></div>
                    <div><strong>DB_DATABASE:</strong> <code>{{ env('DB_DATABASE', '(not set)') }}</code></div>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
