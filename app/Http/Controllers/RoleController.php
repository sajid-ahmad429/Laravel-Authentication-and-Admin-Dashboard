<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Throwable;

class RoleController extends Controller
{
    public function index()
    {
        return view('admin.roles.index', [
            'activeMenu'  => 'roles',
            'permissions' => Permission::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Server-side data feed for the roles table (Tabulator contract).
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

        $query = Role::query()->with('permissions:id,name');

        if ($search !== '') {
            $like = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
            $query->where('name', 'LIKE', $like);
        }

        $total = (clone $query)->count();

        $roles = $query
            ->orderBy('id', $dir)
            ->forPage($page, $size)
            ->get();

        return response()->json([
            'last_page'    => (int) max(1, ceil($total / $size)),
            'total'        => $total,
            'current_page' => $page,
            'data'         => $roles->map(fn (Role $role) => [
                'id'          => $role->id,
                'name'        => $role->name,
                'label'       => ucwords(str_replace('-', ' ', $role->name)),
                'users_count' => DB::table('model_has_roles')
                    ->where('role_id', $role->id)
                    ->count(),
                'permissions' => $role->permissions->map(fn ($p) => $p->name)->values()->all(),
                'protected'   => in_array($role->name, (array) config('auth.protected_roles'), true),
            ])->all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[a-z0-9\-]+$/', Rule::unique('roles', 'name')],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ], [
            'name.regex' => 'Role name may only contain lowercase letters, numbers and dashes.',
        ]);

        $role = Role::create(['name' => $validated['name']]);

        if (! empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()
            ->route('panel.roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate(['id' => ['required', 'integer', 'min:1']]);

        $role = Role::find((int) $validated['id']);

        if (! $role) {
            return response()->json(['status' => 0, 'message' => 'Role not found.'], 404);
        }

        if (in_array($role->name, (array) config('auth.protected_roles'), true)) {
            return response()->json(['status' => 0, 'message' => 'System roles cannot be deleted.'], 403);
        }

        $inUse = DB::table('model_has_roles')->where('role_id', $role->id)->count();

        if ($inUse > 0) {
            return response()->json([
                'status'  => 0,
                'message' => "This role is assigned to {$inUse} user(s) and cannot be deleted.",
            ], 409);
        }

        try {
            $role->delete();
        } catch (Throwable $e) {
            return response()->json(['status' => 0, 'message' => 'Could not delete the role.'], 500);
        }

        return response()->json(['status' => 1, 'message' => 'Role deleted successfully.']);
    }
}
