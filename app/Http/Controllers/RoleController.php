<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $permissions = Permission::all();
        $activeMenu = 'roles';

        return view('admin.roles.index', compact('permissions', 'activeMenu'));
    }

    public function getTableData(Request $request)
    {
        if (!$request->ajax()) {
            return response()->json(['status' => 0, 'message' => 'Invalid Request'], 400);
        }

        $validated = $request->validate([
            'start'          => ['required', 'integer', 'min:0'],
            'length'         => ['required', 'integer', 'min:1'],
            'search.value'   => ['nullable', 'string', 'max:100'],
        ]);

        $query = Role::with('permissions');
        $recordsTotal = Role::count();

        if (!empty($validated['search']['value'])) {
            $search = $validated['search']['value'];
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $recordsFiltered = $query->count();
        $roles = $query->skip($validated['start'])->take($validated['length'])->orderBy('id', 'desc')->get();

        $data = [];
        foreach ($roles as $role) {
            $actionButtons = '
            <div class="dropdown">
                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="mdi mdi-dots-vertical"></i>
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="javascript:void(0);"><i class="mdi mdi-pencil-outline me-1"></i> Edit</a>
                    <form action="' . route('admin.roles.destroy', $role->id) . '" method="POST" style="display:inline;">
                        ' . csrf_field() . '
                        ' . method_field('DELETE') . '
                        <button type="submit" class="dropdown-item text-danger" onclick="return confirm(\'Are you sure?\')"><i class="mdi mdi-trash-can-outline me-1"></i> Trash</button>
                    </form>
                </div>
            </div>';

            $permissionsList = $role->permissions->pluck('name')->map(function ($perm) {
                return '<span class="badge bg-label-primary m-1">' . e($perm) . '</span>';
            })->implode('');

            $data[] = [
                'id'          => $role->id,
                'name'        => ucwords(e($role->name)),
                'permissions' => $permissionsList ?: '<span class="text-muted">None</span>',
                'actions'     => '<div class="text-center">' . $actionButtons . '</div>'
            ];
        }

        return response()->json([
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data
        ]);
    }

    public function create(): View
    {
        $permissions = Permission::all();
        $activeMenu = 'roles';

        return view('admin.roles.create', compact('permissions', 'activeMenu'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'nullable|array',
        ]);

        $role = Role::create(['name' => strtolower($request->input('name'))]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->input('permissions'));
        }

        return redirect()->route('admin.roles.index')->with('success', 'Role created successfully with permissions.');
    }

    public function destroy($id): RedirectResponse
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted successfully.');
    }
}
