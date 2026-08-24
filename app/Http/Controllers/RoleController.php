<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all(); // Sabhi permissions fetch kar li
        $activeMenu = 'roles';

        return view('admin.roles.index', compact('roles', 'permissions', 'activeMenu'));
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

    public function edit($id): View
    {
        $role = Role::findOrFail($id);
        $permissions = Permission::all();
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        $activeMenu = 'roles';

        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions', 'activeMenu'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $role = Role::findOrFail($id);

        $request->validate([
            'name' => 'required|unique:roles,name,' . $role->id,
            'permissions' => 'nullable|array',
        ]);

        $role->update(['name' => strtolower($request->input('name'))]);

        $permissions = $request->input('permissions', []);
        $role->syncPermissions($permissions);

        return redirect()->route('admin.roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy($id): RedirectResponse
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted successfully.');
    }
}
