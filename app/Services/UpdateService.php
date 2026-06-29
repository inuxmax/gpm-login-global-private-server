<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use PclZip;

class UpdateService
{
    /**
     * Apply an update from a ZIP file uploaded by the admin.
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
     * the archive, then run database migrations.
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

    public function migrationDatabase()
    {
        return (new MigrationService())->migrationDatabase();
    }
}
