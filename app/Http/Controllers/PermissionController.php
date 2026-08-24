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
        $activeMenu = 'permissions';
        return view('admin.permissions.index', compact('activeMenu'));
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

        $query = Permission::query();
        $recordsTotal = Permission::count();

        if (!empty($validated['search']['value'])) {
            $search = $validated['search']['value'];
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $recordsFiltered = $query->count();
        $permissions = $query->skip($validated['start'])->take($validated['length'])->orderBy('id', 'desc')->get();

        $data = [];
        foreach ($permissions as $permission) {
            $actionButtons = '
            <div class="dropdown">
                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="mdi mdi-dots-vertical"></i>
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="javascript:void(0);"><i class="mdi mdi-pencil-outline me-1"></i> Edit</a>
                    <form action="' . route('admin.permissions.destroy', $permission->id) . '" method="POST" style="display:inline;">
                        ' . csrf_field() . '
                        ' . method_field('DELETE') . '
                        <button type="submit" class="dropdown-item text-danger" onclick="return confirm(\'Are you sure?\')"><i class="mdi mdi-trash-can-outline me-1"></i> Trash</button>
                    </form>
                </div>
            </div>';

            $data[] = [
                'id'          => $permission->id,
                'name'        => '<span class="badge bg-label-primary">' . e($permission->name) . '</span>',
                'guard_name'  => e($permission->guard_name),
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
