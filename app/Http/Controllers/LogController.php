<?php

namespace App\Http\Controllers;

use App\Services\LogService;
use Illuminate\Http\Request;

class LogController extends Controller
{
    protected $logService;

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }

    public function index(Request $request)
    {
        $this->logService->ensureTableExists();

        $filters = [
            'search' => $request->query('search'),
            'type' => $request->query('type'),
            'target_type' => $request->query('target_type'),
            'target_id' => $request->query('target_id'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'per_page' => (int) $request->query('per_page', 20),
            'page' => (int) $request->query('page', 1),
        ];

        $logs = $this->logService->paginate($filters);

        return response()->json([
            'success' => true,
            'data' => $logs,
            'write_log_enabled' => $this->logService->isEnabled(),
        ]);
    }

    public function destroy($id)
    {
        $ok = $this->logService->delete($id);

        if (!$ok) {
            return response()->json(['success' => false, 'message' => 'log_not_found'], 404);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Delete all logs. Optionally restrict by the same filters used for the
     * listing endpoint so the user can wipe only what they're currently
     * looking at.
     */
    public function destroyAll(Request $request)
    {
        $filters = [
            'search' => $request->input('search'),
            'type' => $request->input('type'),
            'target_type' => $request->input('target_type'),
            'target_id' => $request->input('target_id'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ];

        $deleted = $this->logService->deleteAll($filters);

        return response()->json(['success' => true, 'deleted' => $deleted]);
    }
}
