<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UpdateService;

class UpdateController extends Controller
{
    protected $updateService;

    public function __construct(UpdateService $updateService)
    {
        $this->updateService = $updateService;
    }

    /**
     * Apply an update from a ZIP archive uploaded by the admin SPA.
     * Expects a multipart POST with a single "file" field (.zip).
     */
    public function uploadAndUpdate(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:zip', 'max:512000'],
        ]);

        $result = $this->updateService->updateFromUploadedZip($request->file('file'));
        $status = $result['success'] ? 200 : 422;
        return response()->json($result, $status);
    }

    public static function migrationDatabase()
    {
        $updateService = new \App\Services\UpdateService();
        return $updateService->migrationDatabase();
    }
}
