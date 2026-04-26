<?php

namespace App\Http\Controllers;

use App\Services\SystemLogService;
use Illuminate\Http\Request;

class SystemLogController extends Controller
{
    protected $service;

    public function __construct(SystemLogService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $lines = (int) $request->query('lines', 500);
        if ($lines <= 0) {
            $lines = 500;
        }
        if ($lines > 5000) {
            $lines = 5000;
        }

        $level = $request->query('level');
        $search = $request->query('search');

        $data = $this->service->tail($lines, $level, $search);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function clear()
    {
        $result = $this->service->clear();
        return response()->json(['success' => true, 'data' => $result]);
    }
}
