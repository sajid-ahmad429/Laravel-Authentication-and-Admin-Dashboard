<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Profile Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800 p-8">
    <div class="max-w-3xl mx-auto bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <h1 class="text-2xl font-bold mb-6">Profile Settings</h1>

        @if(session('success'))
            <div class="p-4 mb-6 text-green-800 bg-green-100 rounded-xl">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- Avatar Upload & Preview -->
            <div class="flex items-center gap-6 mb-6">
                <div class="relative w-24 h-24 rounded-full overflow-hidden border-2 border-slate-200 bg-slate-50">
                    @if(!empty($user->avatar))
                        <img src="{{ asset($user->avatar) }}" alt="Avatar" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-slate-400 font-bold text-2xl uppercase">
                            {{ substr($user->name ?? 'U', 0, 2) }}
                        </div>
                    @endif
                </div>
                <div>
                    <label class="block font-medium text-sm mb-1">Profile Photo</label>
                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/jpg,image/webp" class="text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <p class="text-xs text-slate-400 mt-1">Allowed: JPG, PNG, WEBP (Max 2MB). Auto-resized & converted to WebP.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block font-medium mb-1 text-sm">Full Name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required class="w-full px-4 py-2 border rounded-xl">
                </div>
                <div>
                    <label class="block font-medium mb-1 text-sm">Email Address</label>
                    <input type="email" value="{{ $user->email ?? '' }}" disabled class="w-full px-4 py-2 border rounded-xl bg-slate-50 text-slate-400 cursor-not-allowed">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block font-medium mb-1 text-sm">Contact Number</label>
                    <input type="text" name="contact_no" value="{{ old('contact_no', $user->contact_no ?? '') }}" class="w-full px-4 py-2 border rounded-xl">
                </div>
                <div>
                    <label class="block font-medium mb-1 text-sm">Company Name</label>
                    <input type="text" name="company" value="{{ old('company_name', $user->company_name ?? '') }}" class="w-full px-4 py-2 border rounded-xl">
                </div>
                <div>
                    <label class="block font-medium mb-1 text-sm">Country</label>
                    <input type="text" name="country" value="{{ old('country', $user->country ?? '') }}" class="w-full px-4 py-2 border rounded-xl">
                </div>
            </div>

            <hr class="my-6 border-slate-100">

            <h2 class="text-lg font-bold mb-4">Change Password</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block font-medium mb-1 text-sm">New Password</label>
                    <input type="password" name="password" class="w-full px-4 py-2 border rounded-xl" placeholder="Leave blank to keep current">
                </div>
                <div>
                    <label class="block font-medium mb-1 text-sm">Confirm New Password</label>
                    <input type="password" name="password_confirmation" class="w-full px-4 py-2 border rounded-xl" placeholder="Confirm password">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition">Save Changes</button>
            </div>
        </form>
    </div>
</body>
</html>
