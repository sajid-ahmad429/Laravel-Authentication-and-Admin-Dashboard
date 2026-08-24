<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Roles Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800 p-8">
    <div class="max-w-6xl mx-auto bg-white p-6 rounded-xl shadow-md">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Roles Management</h1>
            <a href="{{ route('admin.roles.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                + Create New Role
            </a>
        </div>

        @if(session('success'))
            <div class="p-4 mb-4 text-green-800 bg-green-100 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b bg-slate-50 text-slate-600 text-sm">
                    <th class="p-3">ID</th>
                    <th class="p-3">Role Name</th>
                    <th class="p-3">Permissions</th>
                    <th class="p-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($roles as $role)
                <tr class="border-b hover:bg-slate-50">
                    <td class="p-3">{{ $role->id }}</td>
                    <td class="p-3 font-semibold uppercase">{{ $role->name }}</td>
                    <td class="p-3">
                        <div class="flex flex-wrap gap-1">
                            @forelse($role->permissions as $perm)
                                <span class="px-2 py-0.5 text-xs bg-indigo-50 text-indigo-700 rounded-full border border-indigo-200">
                                    {{ $perm->name }}
                                </span>
                            @empty
                                <span class="text-xs text-slate-400">No permissions</span>
                            @endforelse
                        </div>
                    </td>
                    <td class="p-3 text-right">
                        <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST" onsubmit="return confirm('Delete role?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs px-3 py-1 bg-rose-600 text-white rounded hover:bg-rose-700">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
