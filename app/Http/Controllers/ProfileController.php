<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Http\Requests\UpdateProfileRequest;

class ProfileController extends Controller
{
    public function index(): View
    {
        $user = Auth::user() ?? User::find(session('id'));
        $activeMenu = 'profile';
        return view('admin.profile.index', compact('user', 'activeMenu'));
    }

    public function update(UpdateProfileRequest $request, ImageService $imageService): RedirectResponse
    {
        $userId = session('id') ?? Auth::id();
        $user = User::findOrFail($userId);

        $user->name = $request->input('name');
        $user->contact_no = $request->input('contact_no');
        $user->company_name = $request->input('company');
        $user->country = $request->input('country');

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                $imageService->deleteImage($user->avatar);
            }
            $user->avatar = $imageService->uploadAndOptimize($request->file('avatar'), 'avatars', 500, 500, 80);
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->input('password'));
        }

        $user->save();

        session(['name' => $user->name]);

        return redirect()->back()->with('success', 'Profile settings updated successfully.');
    }
}
