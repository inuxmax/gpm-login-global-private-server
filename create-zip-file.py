import os
import zipfile
from pathlib import Path

# ============================================================
# CẤU HÌNH - Các file/thư mục loại trừ khi nén
# ============================================================

# Loại trừ chung cho CẢ HAI file zip
EXCLUDE_COMMON = [
    ".git",
    ".vs",
    "__pycache__",
    "create-zip-file.py",
    "latest.zip",
    "latest-update.zip",
]

# Loại trừ THÊM cho file "latest-update.zip"
EXCLUDE_UPDATE_DIRS = [
    "vendor",
    "public",
    "storage",
]

EXCLUDE_UPDATE_FILES = [
    "artisan",
    ".env",
    os.path.join("app", "Services", "UpdateService.php"),
    os.path.join("app", "Http", "Controllers", "UpdateController.php"),
]

# ============================================================


def normalize(path: str) -> str:
    """Chuẩn hóa đường dẫn để so sánh (dùng os.sep)."""
    return os.path.normpath(path)


def should_exclude(rel_path: str, extra_dirs: list = None, extra_files: list = None) -> bool:
    """
    Kiểm tra xem một đường dẫn tương đối có bị loại trừ không.
    - rel_path: đường dẫn tương đối tính từ thư mục gốc
    """
    parts = Path(rel_path).parts

    # Kiểm tra loại trừ chung
    for exclude in EXCLUDE_COMMON:
        excl_parts = Path(normalize(exclude)).parts
        # Nếu bất kỳ phần nào của đường dẫn khớp với tên loại trừ
        if excl_parts[0] in parts:
            return True

    # Kiểm tra thư mục loại trừ thêm
    if extra_dirs:
        for d in extra_dirs:
            d_parts = Path(normalize(d)).parts
            if d_parts[0] in parts:
                return True

    # Kiểm tra file loại trừ thêm
    if extra_files:
        for f in extra_files:
            if normalize(rel_path) == normalize(f):
                return True

    return False


def create_zip(zip_name: str, root_dir: str, extra_dirs: list = None, extra_files: list = None):
    """Tạo file zip từ thư mục root_dir với các loại trừ được chỉ định."""
    zip_path = os.path.join(root_dir, zip_name)
    count = 0

    print(f"\n📦 Đang tạo: {zip_name}")
    print(f"   Thư mục nguồn: {root_dir}")

    with zipfile.ZipFile(zip_path, "w", zipfile.ZIP_DEFLATED) as zf:
        for dirpath, dirnames, filenames in os.walk(root_dir):
            # Tính đường dẫn tương đối của thư mục hiện tại
            rel_dir = os.path.relpath(dirpath, root_dir)

            # Loại bỏ các thư mục con bị loại trừ (in-place để os.walk không đi vào)
            dirnames[:] = [
                d for d in dirnames
                if not should_exclude(
                    os.path.join(rel_dir, d) if rel_dir != "." else d,
                    extra_dirs,
                    extra_files
                )
            ]

            # Bỏ qua nếu chính thư mục hiện tại bị loại trừ
            if rel_dir != "." and should_exclude(rel_dir, extra_dirs, extra_files):
                print(f"   ⏭  Bỏ qua thư mục : {rel_dir}")
                continue

            for filename in filenames:
                if rel_dir == ".":
                    rel_file = filename
                else:
                    rel_file = os.path.join(rel_dir, filename)

                if should_exclude(rel_file, extra_dirs, extra_files):
                    print(f"   ⏭  Bỏ qua file : {rel_file}")
                    continue

                abs_file = os.path.join(dirpath, filename)
                zf.write(abs_file, rel_file)
                count += 1

    size_mb = os.path.getsize(zip_path) / (1024 * 1024)
    print(f"   ✅ Hoàn thành! {count} files | Kích thước: {size_mb:.2f} MB")
    print(f"   📁 Lưu tại: {zip_path}")


def main():
    # Thư mục nơi file Python đang đứng
    root_dir = os.path.dirname(os.path.abspath(__file__))

    print("=" * 60)
    print("  🗜  Laravel Project ZIP Creator")
    print("=" * 60)
    print(f"  Thư mục gốc: {root_dir}")
    print(f"  Loại trừ chung: {EXCLUDE_COMMON}")

    # ---- File 1: latest.zip ----
    create_zip(
        zip_name="latest.zip",
        root_dir=root_dir,
    )

    # ---- File 2: latest-update.zip ----
    print(f"\n  Loại trừ thêm (thư mục): {EXCLUDE_UPDATE_DIRS}")
    print(f"  Loại trừ thêm (file)   : {EXCLUDE_UPDATE_FILES}")
    create_zip(
        zip_name="latest-update.zip",
        root_dir=root_dir,
        extra_dirs=EXCLUDE_UPDATE_DIRS,
        extra_files=EXCLUDE_UPDATE_FILES,
    )

    print("\n" + "=" * 60)
    print("  🎉 Tạo ZIP thành công!")
    print("=" * 60)


if __name__ == "__main__":
    main()