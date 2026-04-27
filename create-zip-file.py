import os
import sys
import subprocess
import zipfile
from pathlib import Path

# Force UTF-8 on Windows stdout so emoji icons print correctly when
# piped/redirected (cp1252 default would crash on UnicodeEncodeError).
if hasattr(sys.stdout, "reconfigure"):
    try:
        sys.stdout.reconfigure(encoding="utf-8", errors="replace")
        sys.stderr.reconfigure(encoding="utf-8", errors="replace")
    except Exception:
        pass

# ============================================================
# CẤU HÌNH - Các file/thư mục loại trừ khi nén
# ============================================================

# Loại trừ chung cho CẢ HAI file zip
EXCLUDE_COMMON = [
    ".git",
    ".vs",
    ".idea",
    ".claude",
    "__pycache__",
    "node_modules",
    "create-zip-file.py",
    "latest.zip",
    "latest-update.zip",
]

# File cụ thể (so sánh full-path) loại trừ trong CẢ HAI file zip.
# Khác EXCLUDE_COMMON ở chỗ chỉ match đúng đường dẫn, không match theo segment.
EXCLUDE_COMMON_FILES = [
    # Laravel package-discovery cache: machine-specific (ref các package
    # require-dev như nunomaduro/collision). Phải để server tự regenerate,
    # nếu không sẽ lỗi "Class ... not found".
    os.path.join("bootstrap", "cache", "packages.php"),
    os.path.join("bootstrap", "cache", "services.php"),
    os.path.join("bootstrap", "cache", "config.php"),
    os.path.join("bootstrap", "cache", "routes-v7.php"),
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

# Các đường dẫn này vẫn ĐƯỢC ship trong latest-update.zip mặc dù
# thư mục cha nằm trong EXCLUDE_UPDATE_DIRS. Dùng để đẩy bundle Vue SPA
# (public/build/*) qua auto-update dù toàn bộ public/ bị loại trừ.
FORCE_INCLUDE_UPDATE = [
    os.path.join("public", "build"),
]

# ============================================================


def normalize(path: str) -> str:
    """Chuẩn hóa đường dẫn để so sánh (dùng os.sep)."""
    return os.path.normpath(path)


def path_starts_with(rel_path: str, prefix: str) -> bool:
    """rel_path nằm trong (hoặc bằng) prefix."""
    rel_parts = Path(normalize(rel_path)).parts
    pre_parts = Path(normalize(prefix)).parts
    if len(rel_parts) < len(pre_parts):
        return False
    return rel_parts[: len(pre_parts)] == pre_parts


def is_ancestor_of(rel_path: str, descendant: str) -> bool:
    """rel_path là tổ tiên (strict) của descendant."""
    rel_parts = Path(normalize(rel_path)).parts
    des_parts = Path(normalize(descendant)).parts
    if len(rel_parts) >= len(des_parts):
        return False
    return des_parts[: len(rel_parts)] == rel_parts


def should_exclude(rel_path: str, extra_dirs=None, extra_files=None, force_include=None) -> bool:
    """Có loại trừ file/dir này ra khỏi zip không."""
    # Force include thắng mọi rule exclude
    if force_include:
        for inc in force_include:
            if path_starts_with(rel_path, inc):
                return False

    parts = Path(normalize(rel_path)).parts

    for exclude in EXCLUDE_COMMON:
        if Path(normalize(exclude)).parts[0] in parts:
            return True

    for f in EXCLUDE_COMMON_FILES:
        if normalize(rel_path) == normalize(f):
            return True

    if extra_dirs:
        for d in extra_dirs:
            if Path(normalize(d)).parts[0] in parts:
                return True

    if extra_files:
        for f in extra_files:
            if normalize(rel_path) == normalize(f):
                return True

    return False


def should_walk_dir(rel_path: str, extra_dirs=None, extra_files=None, force_include=None) -> bool:
    """
    Quyết định os.walk có đi vào thư mục này không.
    Phải đi xuyên qua các tổ tiên của force_include (vd: walk vào public/
    để tới được public/build/ dù public/ nằm trong EXCLUDE_UPDATE_DIRS).
    """
    if force_include:
        for inc in force_include:
            if is_ancestor_of(rel_path, inc) or path_starts_with(rel_path, inc):
                return True
    return not should_exclude(rel_path, extra_dirs, extra_files, force_include=None)


def run_npm_build(root_dir: str) -> bool:
    """Chạy `npm run build` để tạo public/build/ (Vue SPA bundle)."""
    print("\n🔧 Đang build Vue SPA (npm run build)...")
    npm_cmd = "npm.cmd" if os.name == "nt" else "npm"
    try:
        subprocess.run(
            [npm_cmd, "run", "build"],
            cwd=root_dir,
            check=True,
            shell=False,
        )
        print("   ✅ Build thành công")
        return True
    except FileNotFoundError:
        print("   ⚠️  Không tìm thấy npm — bỏ qua bước build.")
        print("      public/build/ có thể đang CŨ hoặc KHÔNG TỒN TẠI!")
        return False
    except subprocess.CalledProcessError as e:
        print(f"   ❌ Build thất bại (exit code {e.returncode})")
        print("      Sửa lỗi rồi chạy lại, hoặc dùng --skip-build nếu đã build tay.")
        return False


def verify_spa_artifacts(root_dir: str) -> bool:
    """Kiểm tra public/build/ có đủ để SPA chạy được không."""
    build_dir = os.path.join(root_dir, "public", "build")
    manifest = os.path.join(build_dir, "manifest.json")
    assets_dir = os.path.join(build_dir, "assets")

    problems = []
    if not os.path.isfile(manifest):
        problems.append("thiếu public/build/manifest.json")
    if not os.path.isdir(assets_dir) or not os.listdir(assets_dir):
        problems.append("public/build/assets/ rỗng")

    if problems:
        print(f"\n⚠️  SPA sẽ KHÔNG chạy: {', '.join(problems)}")
        return False

    asset_count = len(os.listdir(assets_dir))
    print(f"\n✅ SPA artifacts OK: manifest.json + {asset_count} file trong assets/")
    return True


def create_zip(zip_name: str, root_dir: str, extra_dirs=None, extra_files=None, force_include=None):
    """Tạo file zip từ thư mục root_dir với các loại trừ được chỉ định."""
    zip_path = os.path.join(root_dir, zip_name)
    count = 0

    print(f"\n📦 Đang tạo: {zip_name}")
    print(f"   Thư mục nguồn: {root_dir}")

    with zipfile.ZipFile(zip_path, "w", zipfile.ZIP_DEFLATED) as zf:
        for dirpath, dirnames, filenames in os.walk(root_dir):
            rel_dir = os.path.relpath(dirpath, root_dir)

            dirnames[:] = [
                d for d in dirnames
                if should_walk_dir(
                    os.path.join(rel_dir, d) if rel_dir != "." else d,
                    extra_dirs,
                    extra_files,
                    force_include,
                )
            ]

            if rel_dir != "." and should_exclude(rel_dir, extra_dirs, extra_files, force_include):
                print(f"   ⏭  Bỏ qua thư mục : {rel_dir}")
                continue

            for filename in filenames:
                rel_file = filename if rel_dir == "." else os.path.join(rel_dir, filename)

                if should_exclude(rel_file, extra_dirs, extra_files, force_include):
                    continue

                abs_file = os.path.join(dirpath, filename)
                zf.write(abs_file, rel_file)
                count += 1

    size_mb = os.path.getsize(zip_path) / (1024 * 1024)
    print(f"   ✅ Hoàn thành! {count} files | Kích thước: {size_mb:.2f} MB")
    print(f"   📁 Lưu tại: {zip_path}")


def main():
    root_dir = os.path.dirname(os.path.abspath(__file__))
    skip_build = "--skip-build" in sys.argv

    print("=" * 60)
    print("  🗜  Laravel Project ZIP Creator")
    print("=" * 60)
    print(f"  Thư mục gốc: {root_dir}")

    if skip_build:
        print("  ⏭  Bỏ qua build (--skip-build)")
    else:
        if not run_npm_build(root_dir):
            print("\n   Dùng --skip-build để nén mà không build.")

    verify_spa_artifacts(root_dir)

    print(f"\n  Loại trừ chung: {EXCLUDE_COMMON}")

    # ---- File 1: latest.zip (full) ----
    create_zip(
        zip_name="latest.zip",
        root_dir=root_dir,
    )

    # ---- File 2: latest-update.zip (chỉ code + SPA bundle) ----
    print(f"\n  Loại trừ thêm (thư mục): {EXCLUDE_UPDATE_DIRS}")
    print(f"  Loại trừ thêm (file)   : {EXCLUDE_UPDATE_FILES}")
    print(f"  Ép ship (override)     : {FORCE_INCLUDE_UPDATE}")
    create_zip(
        zip_name="latest-update.zip",
        root_dir=root_dir,
        extra_dirs=EXCLUDE_UPDATE_DIRS,
        extra_files=EXCLUDE_UPDATE_FILES,
        force_include=FORCE_INCLUDE_UPDATE,
    )

    print("\n" + "=" * 60)
    print("  🎉 Tạo ZIP thành công!")
    print("=" * 60)


if __name__ == "__main__":
    main()
