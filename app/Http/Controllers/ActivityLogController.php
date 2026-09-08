<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.activity_logs.index', [
            'activeMenu' => 'activity_logs',
        ]);
    }

    /**
     * Server-side data feed for the audit trail (Tabulator contract).
     */
    public function getTableData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page'     => ['sometimes', 'integer', 'min:1', 'max:1000000'],
            'size'     => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort_by'  => ['sometimes', 'string', Rule::in(['id', 'logged_at', 'action_type', 'severity'])],
            'sort_dir' => ['sometimes', 'in:asc,desc'],
            'search'   => ['sometimes', 'nullable', 'string', 'max:100'],
            'severity' => ['sometimes', 'nullable', 'in:info,warning,danger,critical'],
            'action'   => ['sometimes', 'nullable', 'string', 'max:50'],
        ]);

        $page   = (int) ($validated['page'] ?? 1);
        $size   = (int) ($validated['size'] ?? 10);
        $sortBy = $validated['sort_by'] ?? 'id';
        $dir    = $validated['sort_dir'] ?? 'desc';
        $search = trim((string) ($validated['search'] ?? ''));
        $sev    = $validated['severity'] ?? null;
        $action = trim((string) ($validated['action'] ?? ''));

        $query = ActivityLog::query()
            ->select(['id', 'user_id', 'user_name', 'user_email', 'method', 'action_type', 'table_name', 'record_id', 'log_text', 'ip_address', 'severity', 'logged_at', 'created_at']);

        if ($sev) {
            $query->where('severity', $sev);
        }

        if ($action !== '') {
            $query->where('action_type', strtoupper($action));
        }

        if ($search !== '') {
            $like = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';

            $query->where(function ($q) use ($like) {
                $q->where('user_name', 'LIKE', $like)
                  ->orWhere('user_email', 'LIKE', $like)
                  ->orWhere('log_text', 'LIKE', $like)
                  ->orWhere('table_name', 'LIKE', $like)
                  ->orWhere('ip_address', 'LIKE', $like);
            });
        }

        $total = (clone $query)->count();

        $logs = $query
            ->orderBy($sortBy, $dir)
            ->orderBy('id', 'desc')
            ->forPage($page, $size)
            ->get();

        return response()->json([
            'last_page'    => (int) max(1, ceil($total / $size)),
            'total'        => $total,
            'current_page' => $page,
            'data'         => $logs->map(fn ($log) => [
                'id'          => $log->id,
                'user_name'   => (string) ($log->user_name ?? 'System'),
                'user_email'  => (string) ($log->user_email ?? ''),
                'action_type' => (string) ($log->action_type ?? 'INFO'),
                'severity'    => (string) ($log->severity ?? 'info'),
                'table_name'  => (string) ($log->table_name ?? '-'),
                'record_id'   => $log->record_id,
                'log_text'    => (string) ($log->log_text ?? ''),
                'ip_address'  => (string) ($log->ip_address ?? '-'),
                'logged_at'   => optional($log->logged_at ?? $log->created_at)->format('d M Y, H:i'),
            ])->all(),
        ]);
    }
}
