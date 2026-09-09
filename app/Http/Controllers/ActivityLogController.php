<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(): View
    {
        $activeMenu = 'activity_logs';
        $logs = ActivityLog::orderBy('id', 'desc')->paginate(15);
        return view('admin.activity_logs.index', compact('activeMenu', 'logs'));
    }

    public function getLogsData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'draw'           => ['nullable', 'integer'],
            'start'          => ['nullable', 'integer', 'min:0'],
            'length'         => ['nullable', 'integer', 'min:1'],
            'search.value'   => ['nullable', 'string', 'max:255'],
        ]);

        $query = ActivityLog::query();

        $recordsTotal = ActivityLog::count();

        if (!empty($validated['search']['value'])) {
            $search = $validated['search']['value'];
            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'LIKE', "%{$search}%")
                  ->orWhere('log_text', 'LIKE', "%{$search}%")
                  ->orWhere('table_name', 'LIKE', "%{$search}%")
                  ->orWhere('action_type', 'LIKE', "%{$search}%");
            });
        }

        $recordsFiltered = $query->count();

        $start = $validated['start'] ?? 0;
        $length = $validated['length'] ?? 10;

        $logs = $query->orderBy('id', 'desc')->skip($start)->take($length)->get();

        $data = [];
        foreach ($logs as $log) {
            $data[] = [
                'id'          => $log->id,
                'user_name'   => e($log->user_name ?? 'System'),
                'action_type' => '<span class="px-2 py-0.5 text-xs font-semibold rounded bg-slate-100 text-slate-800">' . e($log->action_type ?? 'INFO') . '</span>',
                'table_name'  => e($log->table_name ?? '-'),
                'log_text'    => e($log->log_text),
                'ip_address'  => e($log->ip_address ?? '-'),
                'created_at'  => $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '-',
            ];
        }

        return response()->json([
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }
}
