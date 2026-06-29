<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UpdateService;
use Illuminate\Support\Facades\DB;

class UpdateController extends Controller
{
    protected $updateService;

    public function __construct(UpdateService $updateService)
    {
        $this->updateService = $updateService;
    }

    /**
     * Download and update source code from a remote ZIP file.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateFromRemoteZip(Request $request)
    {
        $zipUrl = config('app.update_zip_url');

        $result = $this->updateService->updateFromRemoteZip($zipUrl);
        return redirect()->back()->with('msg', $result['message']);
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