# Deploy lên Dokploy

## Cấu hình Application (Dockerfile)

| Trường | Giá trị |
|--------|---------|
| Build Type | Dockerfile |
| Docker File | `Dockerfile` (không có `/` đầu) |
| Docker Context Path | `.` |
| Port | `80` |

## Biến môi trường tối thiểu

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
DB_CONNECTION=mysql
DB_HOST=gpm_private_server_global_mysql
DB_PORT=3306
DB_DATABASE=gpm_privateserver
DB_USERNAME=gpmuser
DB_PASSWORD=your-password
```

## Lỗi build thường gặp

### `open Dockerfile: no such file or directory`

- Docker File phải là `Dockerfile`, **không** `/Dockerfile`
- Context Path = `.`
- Repo GitHub phải có `Dockerfile` ở thư mục gốc branch đang deploy

### Composer: `codeload.github.com ... HTTP/2 400`

GitHub từ chối tải zip package khi build trên VPS. Dockerfile đã có:

1. Retry `--prefer-dist` 3 lần
2. Fallback `--prefer-source` (git clone)

Push code mới và redeploy. Nếu vẫn fail, thêm **GitHub token** trong Dokploy env:

```env
COMPOSER_AUTH={"github-oauth":{"github.com":"ghp_YOUR_TOKEN"}}
```

Tạo token: GitHub → Settings → Developer settings → Personal access tokens (scope `repo` hoặc public read).

### Sau deploy lần đầu

Vào container hoặc dùng terminal Dokploy:

```bash
php artisan migrate --force
```

Admin UI: `https://your-domain.com/admin/app`
