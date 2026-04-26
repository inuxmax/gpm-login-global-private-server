<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Hand-rolled migration runner used in environments that can't run the
 * `artisan migrate` command (some shared hosts).
 *
 * Lives in its own file (separate from UpdateService) because
 * UpdateService.php is excluded from auto-update zips — see
 * create-zip-file.py — so any change to migration logic placed inside
 * UpdateService would never be shipped via the update flow.
 */
class MigrationService
{
    /**
     * Run every pending migration found in database/migrations.
     *
     * Returns ['success' => bool, 'message' => string, 'executed' => string[]]
     * — `executed` is a human-readable trace, not just migration names.
     */
    public function migrationDatabase(): array
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
                        $executedMigrations[] = 'Execute ' . $sql;
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
                'executed' => $executedMigrations,
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

    private function createMigrationsTable(): void
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

    private function recordMigration(string $migrationName): void
    {
        $batch = DB::table('migrations')->max('batch') ?? 0;
        $batch++;

        DB::table('migrations')->insert([
            'migration' => $migrationName,
            'batch' => $batch,
        ]);
    }
}
