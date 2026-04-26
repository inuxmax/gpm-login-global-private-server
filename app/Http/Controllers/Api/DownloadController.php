<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\UploadService;
use App\Services\S3UploadService;
use App\Services\SettingService;
use App\Services\ProfileService;
use App\Services\LogService;
use App\Models\Log as LogModel;

class DownloadController extends BaseController
{
    protected $logService;
    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }

    public function download($file)
    {
        $file = basename($file);
        
        $fullPath = storage_path('app/public/profiles/' . $file);

        if (!file_exists($fullPath)) {
            abort(404);
        }

        $etag = md5_file($fullPath);

        if (pathinfo($file, PATHINFO_EXTENSION) === '') {
            $profileId = $file;

            $this->logService->create(
                $profileId,
                'profiles',
                LogModel::TYPE_INFO,
                "download profile file: {$file}, egtag: {$etag}"
            );
        }

        return response()->file($fullPath, [
            'etag' => '"' . $etag . '"'
        ]);
    }
}
