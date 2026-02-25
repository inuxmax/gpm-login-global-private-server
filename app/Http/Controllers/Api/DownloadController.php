<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\UploadService;
use App\Services\S3UploadService;
use App\Services\SettingService;
use App\Services\ProfileService;

class DownloadController extends BaseController
{
    public function __construct()
    {
        
    }

    public function download($file)
    {
        $fullPath = storage_path('app/public/profiles/' . $file);

        if (!file_exists($fullPath)) {
            abort(404);
        }

        $etag = md5_file($fullPath);

        return response()->file($fullPath, [
            'etag' => '"' . $etag . '"'
        ]);
    }
}
