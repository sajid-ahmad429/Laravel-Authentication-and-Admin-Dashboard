<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        return view('admin.permissions.index', [
            'activeMenu' => 'permissions',
        ]);
    }

    /**
     * Server-side data feed for the permissions table (Tabulator contract).
     */
    public function getTableData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page'     => ['sometimes', 'integer', 'min:1'],
            'size'     => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort_dir' => ['sometimes', 'in:asc,desc'],
            'search'   => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $page   = (int) ($validated['page'] ?? 1);
        $size   = (int) ($validated['size'] ?? 10);
        $dir    = $validated['sort_dir'] ?? 'desc';
        $search = trim((string) ($validated['search'] ?? ''));

        $query = Permission::query();

        if ($search !== '') {
            $like = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
            $query->where('name', 'LIKE', $like);
        }

        $total = (clone $query)->count();

        $permissions = $query
            ->orderBy('id', $dir)
            ->forPage($page, $size)
            ->get();

        return response()->json([
            'last_page'    => (int) max(1, ceil($total / $size)),
            'total'        => $total,
            'current_page' => $page,
            'data'         => $permissions->map(fn ($permission) => [
                'id'         => $permission->id,
                'name'       => $permission->name,
                'guard_name' => $permission->guard_name,
                'created'    => optional($permission->created_at)->format('d M Y'),
            ])->all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100', 'regex:/^[a-z0-9\-\s]+$/', Rule::unique('permissions', 'name')],
        ], [
            'name.regex' => 'Permission name may only contain lowercase letters, numbers, dashes and spaces.',
        ]);

        Permission::create(['name' => strtolower(trim($validated['name']))]);

        return redirect()
            ->route('panel.permissions.index')
            ->with('success', 'Permission created successfully.');
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate(['id' => ['required', 'integer', 'min:1']]);

        $permission = Permission::find((int) $validated['id']);

        if (! $permission) {
            return response()->json(['status' => 0, 'message' => 'Permission not found.'], 404);
        }

        $inUse = \Illuminate\Support\Facades\DB::table('role_has_permissions')
            ->where('permission_id', $permission->id)
            ->count();

        if ($inUse > 0) {
            return response()->json([
                'status'  => 0,
                'message' => "This permission is used by {$inUse} role(s) and cannot be deleted.",
            ], 409);
        }

        $permission->delete();

        return response()->json(['status' => 1, 'message' => 'Permission deleted successfully.']);
    }
}
