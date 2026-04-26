<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use PclZip;
use Illuminate\Support\Facades\DB;

class UpdateService
{
    public function updateFromRemoteZip(string $zipUrl = 'https://github.com/ngochoaitn/gpm-login-global-private-server/releases/download/latest/latest-update.zip')
    {
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

    public function migrationDatabase()
    {
        try {
            $this->createMigrationsTable();

            $migrationsPath = database_path('migrations');
            $migrationFiles = glob($migrationsPath . '/*.php');
            sort($migrationFiles);

            $executedMigrations = [];
            $errors = [];

            $executedMigrations[] = 'migrationsPath: ' . $migrationsPath;
            if (empty($migrationFiles)) {
                $executedMigrations[] = 'No migration files found';
            }


            foreach ($migrationFiles as $migrationFile) {
                $migrationName = basename($migrationFile, '.php');

                if ($this->migrationAlreadyRun($migrationName)) {
                    $executedMigrations[] = 'Skip ' . $migrationName;
                    continue;
                }

                try {
                    $sqlStatements = $this->convertMigrationToSQL($migrationFile);

                    foreach ($sqlStatements as $sql) {
                        $executedMigrations[]  = 'Execute ' . $sql;
                        if (!empty(trim($sql))) {
                            DB::statement($sql);
                        }
                    }

                    

                    $this->recordMigration($migrationName);
                    $executedMigrations[] = 'Run ' . $migrationName;

                } catch (\Exception $e) {
                    $errors[] = "Migration {$migrationName} failed: " . $e->getMessage();
                }
            }

            if (!empty($errors)) {
                throw new \Exception('Some migrations failed: ' . implode('; ', $errors));
            }

            return [
                'success' => true,
                'message' => 'ok',
                'executed' => $executedMigrations
            ];

        } catch (\Exception $e) {
            throw new \Exception('Migration failed: ' . $e->getMessage());
        }
    }


    /**
     * Load a Laravel migration file and capture every SQL statement its
     * up() method would emit, without executing them. Uses
     * DB::connection()->pretend() so the schema builder generates real
     * driver-specific SQL (CREATE TABLE, ADD COLUMN, ALTER, CREATE INDEX,
     * foreign keys, raw DB::statement calls inside up(), etc.).
     *
     * Modern Laravel migrations are written as `return new class extends
     * Migration { ... };` — `require` returns that instance.
     *
     * Returns inlined SQL strings (bindings substituted) ready to feed
     * into DB::statement(). Returns an empty array for files that don't
     * follow the expected shape.
     */
    private function convertMigrationToSQL(string $migrationFile): array
    {
        if (!is_file($migrationFile)) {
            return [];
        }

        $migration = require $migrationFile;
        if (!is_object($migration) || !method_exists($migration, 'up')) {
            return [];
        }

        $captured = DB::connection()->pretend(function () use ($migration) {
            $migration->up();
        });

        $sqlStatements = [];
        foreach ($captured as $entry) {
            $sql = $entry['query'] ?? '';
            if ($sql === '') {
                continue;
            }
            $bindings = $entry['bindings'] ?? [];
            $sqlStatements[] = $bindings ? $this->inlineBindings($sql, $bindings) : $sql;
        }

        return $sqlStatements;
    }

    /**
     * Replace `?` placeholders in $sql with safely-quoted literal values.
     * Schema migrations rarely have bindings, but a few (DEFAULT '...',
     * comments, raw inserts) do.
     */
    private function inlineBindings(string $sql, array $bindings): string
    {
        $i = 0;
        return preg_replace_callback('/\?/', function () use ($bindings, &$i) {
            if (!array_key_exists($i, $bindings)) {
                return '?';
            }
            $value = $bindings[$i++];
            if (is_null($value)) {
                return 'NULL';
            }
            if (is_bool($value)) {
                return $value ? '1' : '0';
            }
            if (is_int($value) || is_float($value)) {
                return (string) $value;
            }
            return "'" . str_replace("'", "''", (string) $value) . "'";
        }, $sql);
    }


    private function createMigrationsTable()
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS migrations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL
        )";

        DB::statement($sql);
    }

    private function migrationAlreadyRun(string $migrationName): bool
    {
        return DB::table('migrations')
            ->where('migration', $migrationName)
            ->exists();
    }

    private function recordMigration(string $migrationName)
    {
        // Get the next batch number
        $batch = DB::table('migrations')->max('batch') ?? 0;
        $batch++;

        DB::table('migrations')->insert([
            'migration' => $migrationName,
            'batch' => $batch
        ]);
    }
}
