<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Permissions Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800 p-8">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-xl shadow-md">
        <h1 class="text-2xl font-bold mb-6">Permissions Management</h1>

        @if(session('success'))
            <div class="p-4 mb-4 text-green-800 bg-green-100 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('admin.permissions.store') }}" method="POST" class="flex gap-3 mb-6">
            @csrf
            <input type="text" name="name" placeholder="Enter permission name (e.g. edit users)" required class="flex-1 px-4 py-2 border rounded-lg">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Add Permission</button>
        </form>

        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b bg-slate-50 text-slate-600 text-sm">
                    <th class="p-3">ID</th>
                    <th class="p-3">Permission Name</th>
                    <th class="p-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($permissions as $permission)
                <tr class="border-b hover:bg-slate-50">
                    <td class="p-3">{{ $permission->id }}</td>
                    <td class="p-3 font-medium">{{ $permission->name }}</td>
                    <td class="p-3 text-right">
                        <form action="{{ route('admin.permissions.destroy', $permission->id) }}" method="POST" onsubmit="return confirm('Delete permission?');">
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
