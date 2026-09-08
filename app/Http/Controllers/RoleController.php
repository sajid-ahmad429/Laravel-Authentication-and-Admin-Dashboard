<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Role route prefixes that exist in routes/web.php. Used to build safe
     * redirect targets from the session role.
     */
    public const VALID_ROLE_PREFIXES = ['superadmin', 'admin', 'author', 'maintainer', 'editor', 'subscriber'];

    /**
     * Roles that can never be deleted through the UI.
     */
    public const PROTECTED_ROLES = ['superadmin', 'admin'];

    public function index(): View
    {
        $permissions = Permission::orderBy('name')->get();
        $activeMenu = 'roles';

        return view('admin.roles.index', compact('permissions', 'activeMenu'));
    }

    public function getTableData(Request $request)
    {
        if (! $request->ajax()) {
            return response()->json(['status' => 0, 'message' => 'Invalid Request'], 400);
        }

        $validated = $request->validate([
            'draw' => ['nullable', 'integer'],
            'start' => ['required', 'integer', 'min:0'],
            'length' => ['required', 'integer', 'min:1', 'max:100'],
            'search.value' => ['nullable', 'string', 'max:100'],
        ]);

        $query = Role::with('permissions');
        $recordsTotal = Role::count();

        if (! empty($validated['search']['value'])) {
            $search = $validated['search']['value'];
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $recordsFiltered = (clone $query)->count();
        $roles = $query->orderBy('id', 'desc')->skip($validated['start'])->take($validated['length'])->get();

        $data = [];
        foreach ($roles as $role) {
            $destroyRoute = route($this->routePrefix().'.roles.destroy', $role->id);
            $deleteForm = in_array(strtolower($role->name), self::PROTECTED_ROLES, true)
                ? '<span class="dropdown-item-text text-muted"><i class="mdi mdi-lock-outline me-1"></i> Protected</span>'
                : '<form action="'.$destroyRoute.'" method="POST" style="display:inline;">'
                    .csrf_field()
                    .method_field('DELETE')
                    .'<button type="submit" class="dropdown-item text-danger" onclick="return confirm(\'Are you sure?\')"><i class="mdi mdi-trash-can-outline me-1"></i> Delete</button>'
                    .'</form>';

            $actionButtons = '
            <div class="dropdown">
                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="mdi mdi-dots-vertical"></i>
                </button>
                <div class="dropdown-menu">
                    '.$deleteForm.'
                </div>
            </div>';

            $permissionsList = $role->permissions->pluck('name')->map(function ($perm) {
                return '<span class="badge bg-label-primary m-1">'.e($perm).'</span>';
            })->implode('');

            $data[] = [
                'id' => $role->id,
                'name' => ucwords(e($role->name)),
                'permissions' => $permissionsList ?: '<span class="text-muted">None</span>',
                'actions' => '<div class="text-center">'.$actionButtons.'</div>',
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function create(): View
    {
        $permissions = Permission::orderBy('name')->get();
        $activeMenu = 'roles';

        return view('admin.roles.create', compact('permissions', 'activeMenu'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['name' => strtolower(trim((string) $request->input('name', '')))]);

        $request->validate([
            'name' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_\- ]+$/', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create(['name' => $request->input('name')]);

        if ($request->filled('permissions')) {
            $role->syncPermissions($request->input('permissions'));
        }

        return redirect()->route($this->routePrefix().'.roles.index')->with('success', 'Role created successfully with permissions.');
    }

    public function destroy($id): RedirectResponse
    {
        $role = Role::findOrFail($id);

        if (in_array(strtolower($role->name), self::PROTECTED_ROLES, true)) {
            return redirect()->route($this->routePrefix().'.roles.index')->with('danger', 'The "'.$role->name.'" role is protected and cannot be deleted.');
        }

        // Prevent locking every administrator out of role management.
        if ($role->users()->count() > 0) {
            return redirect()->route($this->routePrefix().'.roles.index')->with('danger', 'The "'.$role->name.'" role is still assigned to users and cannot be deleted.');
        }

        $role->delete();

        return redirect()->route($this->routePrefix().'.roles.index')->with('success', 'Role deleted successfully.');
    }

    /**
     * Resolve a safe route prefix from the session role.
     */
    protected function routePrefix(): string
    {
        $role = strtolower((string) session('role', 'admin'));

        return in_array($role, self::VALID_ROLE_PREFIXES, true) ? $role : 'admin';
    }
}
