# Auto-update private server

## Cơ chế

Nút **Cập nhật private server** (`/auto-update`) tải file `latest-update.zip` từ GitHub Releases, giải nén đè code và chạy migration.

Mặc định trỏ repo gốc `ngochoaitn`. Để dùng **repo của bạn**, set biến môi trường:

```env
UPDATE_ZIP_URL=https://github.com/USER/REPO/releases/download/latest/latest-update.zip
```

Ví dụ fork `inuxmax`:

```env
UPDATE_ZIP_URL=https://github.com/inuxmax/gpm-login-global-private-server/releases/download/latest/latest-update.zip
```

Trên **Dokploy**: thêm `UPDATE_ZIP_URL` trong Environment → Redeploy (hoặc `php artisan config:clear` trong container).

## Tạo file release

1. Build frontend (nếu đổi Vue):

```bash
npm install
npm run build
```

2. Tạo zip:

```bash
python create-zip-file.py
```

3. Upload `latest-update.zip` lên GitHub Releases:
   - Tag: **`latest`** (bắt buộc — URL download dùng tag này)
   - Asset: **`latest-update.zip`**

## Upload thủ công (không dùng auto-update remote)

Admin UI → **Cấu hình hệ thống** → **Upload update** — chọn file `.zip` local, không cần `UPDATE_ZIP_URL`.

## Lưu ý khi deploy Docker / Dokploy

Auto-update **ghi đè file trong container**. Trên Dokploy, thay đổi có thể **mất khi redeploy** image mới. Khuyến nghị:

- Cập nhật chính: **git push → Dokploy rebuild/redeploy**
- Auto-update zip: chỉ hotfix nhanh trên server bare-metal / docker volume mount code
