# Cấu hình S3 / S3-compatible storage

## Các trường cấu hình

| Trường UI | Key API / DB | Mô tả |
|-----------|--------------|--------|
| S3 Key | `S3_KEY` / `s3_key` | Access key |
| S3 Secret | `S3_PASSWORD` / `s3_secret` | Secret key |
| S3 Bucket | `S3_BUCKET` / `s3_bucket` | Tên bucket |
| S3 Region | `S3_REGION` / `s3_region` | Region AWS (vd. `APSoutheast1`) hoặc region tùy chỉnh |
| **S3 Custom URL** | `S3_ENDPOINT` / `s3_endpoint` | Endpoint URL cho dịch vụ S3-compatible |

## Khi nào cần Custom URL?

- **AWS S3 thuần**: để trống Custom URL, chỉ chọn region AWS.
- **DigitalOcean Spaces**: nhập endpoint, vd. `https://sgp1.digitaloceanspaces.com`, region chọn hoặc nhập `sgp1`.
- **MinIO / Cloudflare R2 / Wasabi**: nhập endpoint do nhà cung cấp cung cấp.

## Tương thích ngược

Nếu trước đây bạn nhập URL endpoint trực tiếp vào ô **S3 Region** (cách cũ cho DigitalOcean), hệ thống vẫn hoạt động. Khuyến nghị chuyển URL sang ô **S3 Custom URL** và dùng **S3 Region** cho mã region thực tế.

## API trả về cho desktop client

`GET /api/settings/get-s3-api` (Sanctum) trả thêm field:

```json
{
  "s3_api_key": "...",
  "s3_api_secret": "...",
  "s3_api_bucket": "...",
  "s3_api_region": "APSoutheast1",
  "s3_api_endpoint": "https://..."
}
```

## Biến môi trường (tùy chọn)

```env
S3_ENDPOINT=https://your-endpoint.example.com
```

Giá trị sẽ được migrate vào bảng `settings` khi chạy migration `migrate_env_settings_to_database`.
