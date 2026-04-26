<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SqlConsoleController extends Controller
{
    /**
     * Caps to keep the response payload manageable in the browser.
     */
    public const MAX_ROWS = 5000;
    public const MAX_SQL_LENGTH = 100000; // 100 KB

    /**
     * Execute an arbitrary SQL statement and return its result. Admin-only —
     * gated by the admin.only middleware on the route group.
     */
    public function execute(Request $request)
    {
        $request->validate([
            'sql' => ['required', 'string', 'max:' . self::MAX_SQL_LENGTH],
        ]);

        $sql = trim((string) $request->input('sql'));
        if ($sql === '') {
            return response()->json([
                'success' => false,
                'message' => 'sql_is_empty',
            ], 422);
        }

        $kind = $this->classify($sql);
        $start = microtime(true);

        try {
            switch ($kind) {
                case 'select':
                    $rows = DB::select($sql);
                    $rows = array_map(fn ($r) => (array) $r, $rows);

                    $totalRows = count($rows);
                    $truncated = false;
                    if ($totalRows > self::MAX_ROWS) {
                        $rows = array_slice($rows, 0, self::MAX_ROWS);
                        $truncated = true;
                    }

                    $columns = $totalRows > 0 ? array_keys($rows[0]) : [];

                    $payload = [
                        'success' => true,
                        'type' => 'select',
                        'columns' => $columns,
                        'rows' => $rows,
                        'row_count' => $totalRows,
                        'truncated' => $truncated,
                        'duration_ms' => $this->elapsedMs($start),
                    ];
                    break;

                case 'modify':
                    $affected = DB::affectingStatement($sql);
                    $payload = [
                        'success' => true,
                        'type' => 'modify',
                        'affected_rows' => $affected,
                        'duration_ms' => $this->elapsedMs($start),
                    ];
                    break;

                default: // ddl / other
                    DB::statement($sql);
                    $payload = [
                        'success' => true,
                        'type' => 'ddl',
                        'duration_ms' => $this->elapsedMs($start),
                    ];
                    break;
            }

            return response()->json($payload);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'duration_ms' => $this->elapsedMs($start),
            ], 422);
        }
    }

    /**
     * Pick the right DB facade method based on the leading keyword.
     * "select" → DB::select (returns rows)
     * "modify" → DB::affectingStatement (returns affected count)
     * default → DB::statement (DDL / SET / etc.)
     */
    private function classify(string $sql): string
    {
        // Strip leading line comments and whitespace before peeking the verb.
        $stripped = preg_replace('#^(\s*--[^\n]*\n|\s*/\*.*?\*/|\s+)+#s', '', $sql) ?? $sql;
        $first = strtolower(strtok($stripped, " \t\n("));

        if (in_array($first, ['select', 'show', 'describe', 'desc', 'explain', 'pragma', 'with'], true)) {
            return 'select';
        }
        if (in_array($first, ['insert', 'update', 'delete', 'replace'], true)) {
            return 'modify';
        }
        return 'ddl';
    }

    private function elapsedMs(float $startMicrotime): float
    {
        return round((microtime(true) - $startMicrotime) * 1000, 2);
    }
}
