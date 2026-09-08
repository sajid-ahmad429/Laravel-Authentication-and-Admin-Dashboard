<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View
    {
        $user = Auth::user() ?? User::find(session('id'));
        $activeMenu = 'profile';

        return view('admin.profile.index', compact('user', 'activeMenu'));
    }

    public function update(Request $request, ImageService $imageService): RedirectResponse
    {
        $userId = Auth::id() ?? session('id');
        $user = User::findOrFail($userId);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_no' => ['nullable', 'string', 'max:15'],
            'company' => ['nullable', 'string', 'max:150'],
            'country' => ['nullable', 'string', 'max:100'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'password' => ['nullable', 'string', 'min:8', 'max:72', 'confirmed'],
        ]);

        $user->name = $request->input('name');
        $user->contact_no = $request->input('contact_no');
        $user->company_name = $request->input('company');
        $user->country = $request->input('country');

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');

            if (! $file->isValid()) {
                return redirect()->back()->withErrors(['avatar' => 'The uploaded image is invalid.'])->withInput();
            }

            if ($user->avatar) {
                $imageService->deleteImage($user->avatar);
            }

            $user->avatar = $imageService->uploadAndOptimize($file, 'avatars', 500, 500, 80);
        }

        if ($request->filled('password')) {
            // Plaintext assignment: the model's `hashed` cast hashes it.
            // (Never Hash::make() here — that would double-hash the password.)
            $user->password = (string) $request->input('password');
        }

        $user->save();

        session(['name' => $user->name]);

        return redirect()->back()->with('success', 'Profile settings updated successfully.');
    }
}
