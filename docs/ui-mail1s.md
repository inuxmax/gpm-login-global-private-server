# Giao diện Mail1s — Admin GPM

Tài liệu mô tả theme admin được áp dụng từ `F:/theme-mail1s` (Navy + Sky, sidebar, dark mode).

## Nguồn thiết kế

- **Design tokens:** `resources/css/mail1s-tokens.css` (adapt từ `theme-mail1s/tokens.css`)
- **UI guide tham khảo:** `theme-mail1s/ui-guide/` (màu sắc, layout, admin dashboard, dark mode)

## Cấu trúc file

| File | Vai trò |
|------|---------|
| `resources/css/mail1s-tokens.css` | CSS variables light/dark, bridge Element Plus |
| `resources/css/admin.css` | Shell admin: sidebar, topbar, page-card, mobile |
| `resources/js/admin/composables/useTheme.js` | Toggle dark/light, `localStorage.admin_theme` |
| `resources/js/admin/components/SidebarPanel.vue` | Sidebar nhóm menu (Overview / Management / System) |
| `resources/js/admin/layouts/MainLayout.vue` | Layout chính: sidebar, topbar, drawer mobile |
| `resources/views/admin-app.blade.php` | Font Inter, init theme sớm trước mount Vue |
| `resources/views/login.blade.php` | Trang đăng nhập theo Mail1s |
| `resources/views/setup.blade.php` | Trang lỗi DB theo Mail1s |

## Dark mode

1. Script inline trong `admin-app.blade.php` đọc `localStorage.admin_theme` và thêm class `dark` lên `<html>` trước khi Vue mount (tránh flash).
2. `initThemeEarly()` trong `main.js` đồng bộ lại theme.
3. Nút Moon/Sunny trên topbar gọi `toggleTheme()`.

## Sidebar & menu

Menu được nhóm theo i18n:

- **Tổng quan:** Cấu hình hệ thống
- **Quản lý:** Users, Groups, Profiles, Proxies
- **Hệ thống:** Logs, System logs, SQL Console (ẩn)

**SQL Console:** Nhấn `Ctrl+Shift+A` (hoặc `Cmd+Shift+A` trên macOS) để bật/tắt mục SQL Console.

## Mobile

- Sidebar desktop ẩn dưới 768px.
- Burger mở `el-drawer` sidebar (không còn thanh nav ngang trùng menu).

## Page pattern

Các trang danh sách (Profiles, Groups, Proxies, Logs) dùng layout:

```
admin-page
├── AdminPageHeader (banner gradient + actions)
└── page-card admin-page-body
    ├── AdminToolbar (bộ lọc)
    ├── AdminBulkBar (chọn nhiều — tùy trang)
    ├── el-table.admin-table
    └── AdminPagination
```

Component:

| Component | File |
|-----------|------|
| `AdminPageHeader` | `components/AdminPageHeader.vue` |
| `AdminToolbar` | `components/AdminToolbar.vue` |
| `AdminBulkBar` | `components/AdminBulkBar.vue` |
| `AdminPagination` | `components/AdminPagination.vue` |

Class CSS hữu ích:

- `.page-card` — card nội dung có viền accent trên cùng
- `.admin-table` — bảng không viền dày, header uppercase, hover mềm
- `.cell-primary`, `.cell-meta`, `.cell-mono`, `.cell-time` — typography ô bảng
- `.status-pill`, `.badge-soft` — tag trạng thái
- `.admin-action-btns` — nhóm nút hành động trong bảng

## Build frontend

```bash
npm install
npm run build
```

Sau deploy, chạy build trên CI hoặc local rồi commit `public/build` (nếu repo không build trên Docker).

## Tùy chỉnh màu

Sửa biến trong `mail1s-tokens.css`:

- `--brand` — màu chủ đạo (Sky blue)
- `--sidebar-*` — sidebar light/dark
- `--body-gradient` — nền gradient toàn app

Tham chiếu đầy đủ palette tại `theme-mail1s/ui-guide/01-mau-sac.md`.
