<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public const VALID_ROLE_PREFIXES = ['superadmin', 'admin', 'author', 'maintainer', 'editor', 'subscriber'];

    public function index(): View
    {
        $activeMenu = 'permissions';

        return view('admin.permissions.index', compact('activeMenu'));
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

        $query = Permission::query();
        $recordsTotal = Permission::count();

        if (! empty($validated['search']['value'])) {
            $search = $validated['search']['value'];
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $recordsFiltered = (clone $query)->count();
        $permissions = $query->orderBy('id', 'desc')->skip($validated['start'])->take($validated['length'])->get();

        $data = [];
        foreach ($permissions as $permission) {
            $actionButtons = '
            <div class="dropdown">
                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="mdi mdi-dots-vertical"></i>
                </button>
                <div class="dropdown-menu">
                    <form action="'.route($this->routePrefix().'.permissions.destroy', $permission->id).'" method="POST" style="display:inline;">'
                        .csrf_field()
                        .method_field('DELETE')
                        .'<button type="submit" class="dropdown-item text-danger" onclick="return confirm(\'Are you sure?\')"><i class="mdi mdi-trash-can-outline me-1"></i> Delete</button>'
                    .'</form>
                </div>
            </div>';

            $data[] = [
                'id' => $permission->id,
                'name' => '<span class="badge bg-label-primary">'.e($permission->name).'</span>',
                'guard_name' => e($permission->guard_name),
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

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['name' => strtolower(trim((string) $request->input('name', '')))]);

        $request->validate([
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_\- ]+$/', 'unique:permissions,name'],
        ]);

        Permission::create(['name' => $request->input('name')]);

        return redirect()->route($this->routePrefix().'.permissions.index')->with('success', 'Permission created successfully.');
    }

    public function destroy($id): RedirectResponse
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();

        return redirect()->route($this->routePrefix().'.permissions.index')->with('success', 'Permission deleted successfully.');
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
