<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function index(): View
    {
        $permissions = Permission::all();
        $activeMenu = 'permissions';
        return view('admin.permissions.index', compact('permissions', 'activeMenu'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|unique:permissions,name',
        ]);

        Permission::create(['name' => strtolower($request->input('name'))]);

        return redirect()->route('admin.permissions.index')->with('success', 'Permission created successfully.');
    }

    public function destroy($id): RedirectResponse
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();

        return redirect()->route('admin.permissions.index')->with('success', 'Permission deleted successfully.');
    }
}
