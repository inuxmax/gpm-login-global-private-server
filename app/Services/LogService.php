<?php

namespace App\Services;

use App\Models\Log as LogModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class LogService
{
    public const WRITE_LOG_CACHE_KEY = 'setting:write_log';
    public const WRITE_LOG_CACHE_TTL = 300; // seconds

    /**
     * Per-request memoization — within a single request the answer cannot
     * change, so we resolve it at most once. Reset on each new PHP process.
     */
    private static ?bool $isEnabledMemo = null;

    protected $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Whether logging is enabled in settings.
     *
     * Two-layer cache:
     *  - static property: 1 lookup per request (zero cost on subsequent calls)
     *  - Cache::remember: shared across requests, TTL 5 min, busted on save
     */
    public function isEnabled(): bool
    {
        if (self::$isEnabledMemo !== null) {
            return self::$isEnabledMemo;
        }

        $value = Cache::remember(
            self::WRITE_LOG_CACHE_KEY,
            self::WRITE_LOG_CACHE_TTL,
            fn () => $this->settingService->get('write_log', 'off')
        );

        return self::$isEnabledMemo = ($value === 'on');
    }

    /**
     * Drop both cache layers. Call after the write_log setting changes so
     * the new value takes effect immediately (instead of after TTL).
     */
    public static function flushEnabledCache(): void
    {
        self::$isEnabledMemo = null;
        Cache::forget(self::WRITE_LOG_CACHE_KEY);
    }

    /**
     * Per-request memo so we don't re-check the table on every call.
     * Resets between PHP processes — fine since the table only ever goes
     * from "missing" to "present" within a process.
     */
    private static ?bool $tableEnsuredMemo = null;

    /**
     * Make sure the `logs` table exists. If not, run the bundled SQL file
     * (database/migrations/create-log-table.sql) to create it.
     *
     * Safe to call from any hot path — short-circuits via a static memo
     * after the first successful check. Errors are logged to laravel.log
     * but never thrown so callers don't have to worry about them.
     */
    public function ensureTableExists(): bool
    {
        if (self::$tableEnsuredMemo === true) {
            return true;
        }

        try {
            if (Schema::hasTable('logs')) {
                self::$tableEnsuredMemo = true;
                return true;
            }

            $sqlFile = database_path('migrations/create-log-table.sql');
            if (!is_file($sqlFile)) {
                Log::warning('LogService::ensureTableExists missing SQL file', ['path' => $sqlFile]);
                return false;
            }

            $sql = (string) file_get_contents($sqlFile);
            // Strip line comments so naive split-by-`;` doesn't choke on
            // semicolons inside `-- ...` lines.
            $sql = preg_replace('/^\s*--[^\n]*$/m', '', $sql);

            foreach (preg_split('/;\s*(?=(?:[^\']*\'[^\']*\')*[^\']*$)/', $sql) as $statement) {
                $statement = trim((string) $statement);
                if ($statement === '') {
                    continue;
                }
                DB::statement($statement);
            }

            self::$tableEnsuredMemo = Schema::hasTable('logs');

            return self::$tableEnsuredMemo;
        } catch (\Throwable $e) {
            Log::error('LogService::ensureTableExists failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return false;
        }
    }

    /**
     * Create a log entry. Respects the write_log setting — when disabled,
     * nothing is written and null is returned.
     *
     * @param string|null $targetId   profile_id, group_id, ...
     * @param string|null $targetType group, profile, proxy, ...
     * @param string $type            one of LogModel::TYPES
     * @param string|null $message
     */
    public function create(?string $targetId, ?string $targetType, string $type, ?string $message = null): ?LogModel
    {
        if (!$this->isEnabled()) {
            return null;
        }

        if (!in_array($type, LogModel::TYPES, true)) {
            $type = LogModel::TYPE_INFO;
        }

        return LogModel::create([
            'time' => Carbon::now(),
            'target_id' => $targetId,
            'target_type' => $targetType,
            'type' => $type,
            'message' => $message,
        ]);
    }

    /**
     * Update an existing log entry.
     */
    public function update(string $id, array $data): ?LogModel
    {
        $log = LogModel::find($id);
        if (!$log) {
            return null;
        }

        $allowed = ['time', 'target_id', 'target_type', 'type', 'message'];
        $payload = array_intersect_key($data, array_flip($allowed));

        if (isset($payload['type']) && !in_array($payload['type'], LogModel::TYPES, true)) {
            unset($payload['type']);
        }

        if (!empty($payload)) {
            $log->update($payload);
        }

        return $log->fresh();
    }

    /**
     * Delete a single log entry. Returns true when a row was removed.
     */
    public function delete(string $id): bool
    {
        $log = LogModel::find($id);
        if (!$log) {
            return false;
        }
        return (bool) $log->delete();
    }

    /**
     * Delete all log entries (optionally restricted by the same filters used
     * for listing). Returns the deleted-row count.
     */
    public function deleteAll(array $filters = []): int
    {
        return $this->buildQuery($filters)->delete();
    }

    /**
     * Paginated list with keyword search and date/type filters.
     *
     * Filters:
     *  - search   : keyword matched against target_id/type/message
     *  - type     : one of info|warn|error
     *  - from     : datetime (inclusive)
     *  - to       : datetime (inclusive)
     *  - per_page : int (default 20)
     *  - page     : int
     */
    public function paginate(array $filters = [])
    {
        $perPage = (int) ($filters['per_page'] ?? 20);
        if ($perPage <= 0) {
            $perPage = 20;
        }
        $page = $filters['page'] ?? null;

        return $this->buildQuery($filters)
            ->with(['user:id,email,display_name'])
            ->orderByDesc('time')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    private function buildQuery(array $filters)
    {
        $query = LogModel::query();

        if (!empty($filters['search'])) {
            $kw = trim((string) $filters['search']);
            $query->where(function ($q) use ($kw) {
                $q->where('message', 'like', "%{$kw}%")
                    ->orWhere('target_id', 'like', "%{$kw}%")
                    ->orWhere('target_type', 'like', "%{$kw}%")
                    ->orWhere('type', 'like', "%{$kw}%");
            });
        }

        if (!empty($filters['type'])) {
            $type = (string) $filters['type'];
            if (in_array($type, LogModel::TYPES, true)) {
                $query->where('type', $type);
            }
        }

        if (!empty($filters['target_type'])) {
            $query->where('target_type', (string) $filters['target_type']);
        }

        if (!empty($filters['from'])) {
            try {
                $query->where('time', '>=', Carbon::parse($filters['from']));
            } catch (\Throwable $e) {
                // ignore invalid date input
            }
        }

        if (!empty($filters['to'])) {
            try {
                $query->where('time', '<=', Carbon::parse($filters['to']));
            } catch (\Throwable $e) {
                // ignore invalid date input
            }
        }

        return $query;
    }
}
