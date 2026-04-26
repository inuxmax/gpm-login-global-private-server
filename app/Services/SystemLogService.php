<?php

namespace App\Services;

class SystemLogService
{
    /**
     * Cap how much of the file we read from the end (bytes). Laravel logs can
     * grow to hundreds of MB; loading them all is not practical for the UI.
     */
    public const MAX_TAIL_BYTES = 2 * 1024 * 1024; // 2 MB

    public const LEVELS = [
        'debug', 'info', 'notice', 'warning',
        'error', 'critical', 'alert', 'emergency',
    ];

    public function path(): string
    {
        return storage_path('logs/laravel.log');
    }

    /**
     * Tail the laravel.log file. Returns parsed entries (newest first) plus
     * file metadata so the UI can show size and whether the read was capped.
     *
     * @param int $maxEntries upper bound on returned entries
     * @param string|null $level filter by exact level (debug..emergency)
     * @param string|null $search case-insensitive substring against entry text
     */
    public function tail(int $maxEntries = 500, ?string $level = null, ?string $search = null): array
    {
        $path = $this->path();
        $exists = is_file($path);
        $size = $exists ? (int) @filesize($path) : 0;

        if (!$exists || $size === 0) {
            return [
                'entries' => [],
                'size' => $size,
                'path' => 'storage/logs/laravel.log',
                'exists' => $exists,
                'truncated_read' => false,
            ];
        }

        $offset = max(0, $size - self::MAX_TAIL_BYTES);
        $fp = fopen($path, 'r');
        if ($fp === false) {
            return [
                'entries' => [],
                'size' => $size,
                'path' => 'storage/logs/laravel.log',
                'exists' => true,
                'truncated_read' => false,
            ];
        }
        if ($offset > 0) {
            fseek($fp, $offset);
        }
        $content = stream_get_contents($fp);
        fclose($fp);

        $entries = $this->parse((string) $content);

        if ($level !== null && $level !== '') {
            $level = strtolower($level);
            $entries = array_values(array_filter(
                $entries,
                fn ($e) => $e['level'] === $level
            ));
        }

        if ($search !== null && $search !== '') {
            $kw = strtolower($search);
            $entries = array_values(array_filter(
                $entries,
                fn ($e) => str_contains(strtolower($e['raw']), $kw)
            ));
        }

        // Newest first, capped.
        $entries = array_reverse($entries);
        if (count($entries) > $maxEntries) {
            $entries = array_slice($entries, 0, $maxEntries);
        }

        return [
            'entries' => $entries,
            'size' => $size,
            'path' => 'storage/logs/laravel.log',
            'exists' => true,
            'truncated_read' => $offset > 0,
        ];
    }

    /**
     * Truncate the laravel.log file. Returns the number of bytes removed.
     */
    public function clear(): array
    {
        $path = $this->path();
        if (!is_file($path)) {
            return ['cleared' => 0, 'exists' => false];
        }

        $size = (int) @filesize($path);
        $ok = @file_put_contents($path, '') !== false;

        return [
            'cleared' => $ok ? $size : 0,
            'exists' => true,
            'success' => $ok,
        ];
    }

    /**
     * Parse a chunk of laravel.log text into discrete entries. Each entry
     * starts with `[timestamp] env.LEVEL:` and may span multiple lines
     * (stack traces, JSON context).
     */
    private function parse(string $content): array
    {
        if ($content === '') {
            return [];
        }

        $pattern = '/^\[(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:[+\-]\d{2}:?\d{2}|Z)?)\]\s+([\w-]+)\.([A-Z]+):\s*(.*)$/m';

        if (!preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $entries = [];
        $count = count($matches[0]);
        for ($i = 0; $i < $count; $i++) {
            $start = $matches[0][$i][1];
            $end = ($i + 1 < $count) ? $matches[0][$i + 1][1] : strlen($content);
            $raw = rtrim(substr($content, $start, $end - $start));

            $entries[] = [
                'time' => $matches[1][$i][0],
                'env' => $matches[2][$i][0],
                'level' => strtolower($matches[3][$i][0]),
                'message' => $this->shorten(trim($matches[4][$i][0])),
                'raw' => $raw,
            ];
        }

        return $entries;
    }

    private function shorten(string $message, int $max = 240): string
    {
        if (strlen($message) <= $max) {
            return $message;
        }
        return mb_substr($message, 0, $max) . '…';
    }
}
