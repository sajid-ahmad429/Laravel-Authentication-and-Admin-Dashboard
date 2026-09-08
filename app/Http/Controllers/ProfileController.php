<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\ValidPassword;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->attributes->get('sessionUser') ?? User::find(session('id'));

        abort_unless($user, 403);

        return view('admin.profile.index', [
            'user'       => $user,
            'activeMenu' => 'profile',
        ]);
    }

    public function update(Request $request, ImageService $imageService): RedirectResponse
    {
        $user = $request->attributes->get('sessionUser') ?? User::find(session('id'));

        if (! $user) {
            return back()->with('danger', 'Session expired. Please sign in again.');
        }

        $request->validate([
            'name'       => ['required', 'string', 'min:2', 'max:120', 'regex:/^[\pL\pN\s.\-\'&]+$/u'],
            'contact_no' => ['nullable', 'string', 'max:15', 'regex:/^[0-9+\-\s()]+$/'],
            'company'    => ['nullable', 'string', 'max:150'],
            'country'    => ['nullable', 'string', 'max:100'],
            'avatar'     => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048', 'dimensions:min_width=50,min_height=50'],
            'password'   => ['nullable', 'string', 'confirmed', ValidPassword::rules()],
        ], [
            'password.confirmed' => 'Password confirmation does not match.',
            'avatar.dimensions'  => 'The avatar image is too small.',
        ]);

        $user->name         = $request->input('name');
        $user->contact_no   = $request->input('contact_no');
        $user->company_name = $request->input('company');
        $user->country      = $request->input('country');

        if ($request->hasFile('avatar')) {
            $old = $user->avatar;
            $user->avatar = $imageService->uploadAndOptimize($request->file('avatar'), 'avatars', 500, 500, 82);

            if ($old) {
                $imageService->deleteImage($old);
            }
        }

        // A password change requires the CURRENT password.
        if ($request->filled('password')) {
            if (! Hash::check((string) $request->input('current_password'), $user->password)) {
                return back()
                    ->withInput($request->except('password', 'password_confirmation', 'current_password'))
                    ->withErrors(['current_password' => 'Your current password is incorrect.']);
            }

            $user->password = $request->input('password'); // hashed by model cast

            // Invalidate other sessions/devices.
            \App\Models\AuthModel::deleteTokenByUserId((int) $user->id);
            session()->forget('rememberme');
        }

        $user->save();

        session(['name' => $user->name]);

        return back()->with('success', 'Profile updated successfully.');
    }
}
