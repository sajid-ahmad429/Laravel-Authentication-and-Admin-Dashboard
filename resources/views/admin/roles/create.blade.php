<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Role</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800 p-8">
    <div class="max-w-2xl mx-auto bg-white p-6 rounded-xl shadow-md">
        <h1 class="text-2xl font-bold mb-6">Create New Role</h1>

        <form action="{{ route('admin.roles.store') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block font-medium mb-2">Role Name</label>
                <input type="text" name="name" required class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-indigo-200">
            </div>

            <div class="mb-6">
                <label class="block font-medium mb-2">Assign Permissions</label>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($permissions as $permission)
                        <label class="flex items-center space-x-2 text-sm bg-slate-50 p-2 rounded border">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" class="rounded text-indigo-600">
                            <span>{{ $permission->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.roles.index') }}" class="px-4 py-2 bg-slate-200 rounded-lg hover:bg-slate-300">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Save Role</button>
            </div>
        </form>
    </div>
</body>
</html>
