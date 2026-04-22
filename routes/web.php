<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminApiController;
use App\Http\Controllers\UpdateController;

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

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/auto-update', [UpdateController::class, 'updateFromRemoteZip']);
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

        Route::get('/users', [AdminApiController::class, 'getUsers']);
        Route::post('/users/{id}/toggle-active', [AdminApiController::class, 'toggleActiveUser']);
        Route::post('/users/{id}/reset-password', [AdminApiController::class, 'resetUserPassword']);

        Route::post('/reset-profile-status', [AdminApiController::class, 'resetProfileStatus']);
        Route::post('/run-migrations', [AdminApiController::class, 'runMigrations']);

        Route::post('/logout', [AdminApiController::class, 'logout']);

        // Admin-only filesystem lookups (SPA uses /api/profiles for CRUD)
        Route::get('/profiles/storage-sizes', [AdminApiController::class, 'profileStorageSizes']);
    });
});
