<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminApiController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\SqlConsoleController;
use App\Http\Controllers\SystemLogController;
use App\Http\Controllers\UpdateController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProxyController;
use App\Http\Controllers\Api\UserController as ApiUserController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [HomeController::class, 'index']);
Route::get('/setup', [HomeController::class, 'setup']);
Route::post('/setup', [HomeController::class, 'createDb']);
Route::get('/test', [HomeController::class, 'test']);
Route::get('/test', function () {
    return 'test';
});

Route::get('/admin/auth', function () {
    return view('login');
})->name('login');
Route::get('/admin/auth/logout', [AuthController::class, 'logout']);
Route::post('/admin/auth', [AuthController::class, 'login']);


Route::get('/admin', [AdminController::class, 'index']);
Route::get('/admin/active-user/{id}', [AdminController::class, 'toogleActiveUser']);
Route::get('/admin/reset-user-password/{id}', [AdminController::class, 'resetUserPassword']);
Route::get('/admin/reset-profile-status', [AdminController::class, 'resetProfileStatus']);
Route::get('/admin/save-setting', [AdminController::class, 'saveSetting']);
Route::get('/admin/migration', [AdminController::class, 'runMigrations']);

Route::middleware(['auth:sanctum'])->get('/phpinfo', function () {
    phpinfo();
});

// ========================================
// New SPA (Vue 3) admin UI
// ========================================
Route::middleware(['admin.only'])->group(function () {
    // SPA shell — all /admin/app/* paths fall through to Vue Router.
    // SPA reuses /api/* endpoints via session cookie auth (see config/sanctum.php).
    Route::get('/admin/app', fn () => view('admin-app'));
    Route::get('/admin/app/{any}', fn () => view('admin-app'))->where('any', '.*');

    // Admin JSON API consumed by the SPA
    Route::prefix('admin/api')->group(function () {
        Route::get('/me', [AdminApiController::class, 'me']);

        Route::get('/settings', [AdminApiController::class, 'getSettings']);
        Route::post('/settings', [AdminApiController::class, 'saveSettings']);
        Route::post('/settings/test-s3', [AdminApiController::class, 'testS3Connection']);

        Route::get('/users', [AdminApiController::class, 'getUsers']);
        Route::post('/users/{id}/toggle-active', [AdminApiController::class, 'toggleActiveUser']);
        Route::post('/users/{id}/reset-password', [AdminApiController::class, 'resetUserPassword']);

        Route::post('/reset-profile-status', [AdminApiController::class, 'resetProfileStatus']);
        Route::post('/run-migrations', [AdminApiController::class, 'runMigrations']);
        Route::post('/upload-update', [UpdateController::class, 'uploadAndUpdate']);

        Route::post('/logout', [AdminApiController::class, 'logout']);

        Route::prefix('logs')->group(function () {
            Route::get('/', [LogController::class, 'index']);
            Route::post('/delete-all', [LogController::class, 'destroyAll']);
            Route::post('/{id}/delete', [LogController::class, 'destroy']);
        });

        Route::prefix('system-logs')->group(function () {
            Route::get('/', [SystemLogController::class, 'index']);
            Route::post('/clear', [SystemLogController::class, 'clear']);
        });

        Route::prefix('sql')->group(function () {
            Route::post('/execute', [SqlConsoleController::class, 'execute']);
        });

        // User picker for ShareDialog (paginated active users) — reuses Api\UserController
        Route::get('/user-search', [ApiUserController::class, 'index']);

        // Admin-only filesystem lookups. Must come BEFORE the /profiles/{id}
        // route registered below, otherwise Laravel matches "storage-sizes" as {id}.
        Route::get('/profiles/storage-sizes', [AdminApiController::class, 'profileStorageSizes']);

        // --------------------------------------------------------------------
        // Re-mount existing Api\* controllers for the admin SPA.
        // Same controllers as routes/api.php — session auth via admin.only
        // instead of Sanctum, so desktop-app Bearer tokens are untouched and
        // we no longer need SANCTUM_STATEFUL_DOMAINS=* for the SPA.
        // --------------------------------------------------------------------

        Route::prefix('groups')->group(function () {
            Route::get('/', [GroupController::class, 'index']);
            Route::get('/count', [GroupController::class, 'getTotal']);
            Route::post('/create', [GroupController::class, 'store']);
            Route::post('/update/{id}', [GroupController::class, 'update']);
            Route::post('/delete/{id}', [GroupController::class, 'destroy']);
            Route::post('/share/{id}', [GroupController::class, 'share']);
            Route::post('/remove-share/{id}', [GroupController::class, 'removeShare']);
            Route::get('/get-share-users/{id}', [GroupController::class, 'getGroupShareUsers']);
            Route::get('/{id}', [GroupController::class, 'show']);
        });

        Route::prefix('profiles')->group(function () {
            Route::get('/', [ProfileController::class, 'index']);
            Route::get('/count', [ProfileController::class, 'getTotal']);
            Route::post('/create', [ProfileController::class, 'store']);
            Route::post('/update/{id}', [ProfileController::class, 'update']);
            Route::post('/bulk-edit-property', [ProfileController::class, 'bulkEditProperty']);
            Route::post('/delete/{id}', [ProfileController::class, 'destroy']);
            Route::post('/bulk-delete', [ProfileController::class, 'bulkDelete']);
            Route::post('/share/{id}', [ProfileController::class, 'share']);
            Route::post('/remove-share/{id}', [ProfileController::class, 'removeShare']);
            Route::post('/bulk-share', [ProfileController::class, 'bulkShare']);
            Route::post('/bulk-remove-share', [ProfileController::class, 'bulkRemoveShare']);
            Route::post('/update-status/{id}', [ProfileController::class, 'updateStatus']);
            Route::post('/restore/{id}', [ProfileController::class, 'restore']);
            Route::post('/bulk-restore', [ProfileController::class, 'bulkRestore']);
            Route::get('/get-share-users/{id}', [ProfileController::class, 'getProfileShareUsers']);
            Route::get('/{id}', [ProfileController::class, 'show']);
        });

        Route::prefix('proxies')->group(function () {
            Route::get('/', [ProxyController::class, 'index']);
            Route::post('/bulk-create', [ProxyController::class, 'bulkStore']);
            Route::post('/update/{id}', [ProxyController::class, 'update']);
            Route::post('/delete/{id}', [ProxyController::class, 'destroy']);
            Route::post('/bulk-delete', [ProxyController::class, 'bulkDelete']);
            Route::post('/bulk-share', [ProxyController::class, 'bulkShare']);
            Route::post('/remove-share/{id}', [ProxyController::class, 'removeShare']);
            Route::post('/bulk-remove-share', [ProxyController::class, 'bulkRemoveShare']);
            Route::get('/get-share-users/{id}', [ProxyController::class, 'getProxyShareUsers']);
            Route::get('/{id}', [ProxyController::class, 'show']);
        });
    });
});
