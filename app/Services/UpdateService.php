<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use PclZip;
use Illuminate\Support\Facades\DB;

class UpdateService
{
    public function updateFromRemoteZip(?string $zipUrl = null)
    {
        $zipUrl = $zipUrl ?: config('app.update_zip_url');
        $zipFileName = 'update.zip';
        $zipFilePath = storage_path('app/' . $zipFileName);

        try {
            if (!$this->downloadFileFromUrl($zipUrl, $zipFilePath)) {
                return ['success' => false, 'message' => 'Cannot download ZIP file'];
            }

            return $this->extractAndMigrate($zipFilePath, $zipFileName);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Apply an update from a ZIP file uploaded by the admin (no remote
     * download). Same extract + migration pipeline as updateFromRemoteZip.
     */
    public function updateFromUploadedZip(\Illuminate\Http\UploadedFile $uploadedFile): array
    {
        if (!$uploadedFile->isValid()) {
            return ['success' => false, 'message' => 'Invalid uploaded file'];
        }

        $ext = strtolower($uploadedFile->getClientOriginalExtension());
        if ($ext !== 'zip') {
            return ['success' => false, 'message' => 'Uploaded file must be a .zip archive'];
        }

        $zipFileName = 'update.zip';
        $targetDir = storage_path('app');
        $zipFilePath = $targetDir . DIRECTORY_SEPARATOR . $zipFileName;

        try {
            if (!is_dir($targetDir)) {
                @mkdir($targetDir, 0755, true);
            }
            if (is_file($zipFilePath)) {
                @unlink($zipFilePath);
            }

            $uploadedFile->move($targetDir, $zipFileName);

            return $this->extractAndMigrate($zipFilePath, $zipFileName);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Extract a prepared ZIP at $zipFilePath into the project root, remove
     * the archive, then run database migrations. Shared by remote-download
     * and admin-upload update flows.
     */
    private function extractAndMigrate(string $zipFilePath, string $zipFileName): array
    {
        $archive = new PclZip($zipFilePath);
        $destination = base_path();

        if ($archive->extract(PCLZIP_OPT_PATH, $destination,
                PCLZIP_OPT_REPLACE_NEWER) == 0) {
            return ['success' => false, 'message' => 'Failed to extract the ZIP file'];
        }

        Storage::delete($zipFileName);

        try {
            $migrationResult = $this->migrationDatabase();
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Migration failed: ' . $e->getMessage()];
        }

        return [
            'success' => true,
            'message' => 'ok',
            'executed' => $migrationResult['executed'] ?? [],
        ];
    }



    private function downloadFileFromUrl(string $url, string $fileName)
    {
        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) coc_coc_browser/87.0.152 Chrome/81.0.4044.152 Safari/537.36\r\n" .
                    "Accept: */*\r\n" .
                    "Accept: */*\r\n" .
                    "Accept-Encoding: gzip, deflate, br\r\n"
            ],
            "https" => [
                "method" => "GET",
                "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) coc_coc_browser/87.0.152 Chrome/81.0.4044.152 Safari/537.36\r\n" .
                    "Accept: */*\r\n" .
                    "Accept: */*\r\n" .
                    "Accept-Encoding: gzip, deflate, br\r\n"
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        $context = stream_context_create($opts);

        $content = @file_get_contents($url, false, $context);
        if ($content != false) {
            file_put_contents($fileName, $content);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Thin proxy kept for backward compatibility (UpdateController and any
     * external callers still reference it). Real implementation lives in
     * MigrationService — UpdateService.php is excluded from auto-update
     * zips, so put migration logic anywhere else.
     */
    public function migrationDatabase()
    {
        return (new MigrationService())->migrationDatabase();
    }
}
