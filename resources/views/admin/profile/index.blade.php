@section('title', 'My Profile')
@include('Admin.templates.header')


@php
    $initials = collect(explode(' ', (string) ($user->name ?? 'U')))
        ->filter()->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
@endphp

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h4 class="fw-bold mb-1">Profile Settings</h4>
                <p class="text-muted mb-0">Update your personal information, avatar and password.</p>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('panel.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Profile</li>
                </ol>
            </nav>
        </div>

        <div class="card border-0 shadow-sm" style="max-width: 860px;">
            <div class="card-body p-4">
                <form action="{{ route('panel.profile.update') }}" method="POST" enctype="multipart/form-data" id="profileForm">
                    @csrf

                    <div class="d-flex align-items-center gap-4 mb-4 flex-wrap">
                        <div class="avatar avatar-xl">
                            @if(!empty($user->avatar))
                                <img src="{{ asset($user->avatar) }}" alt="Avatar" class="rounded-circle" width="80" height="80" style="object-fit:cover">
                            @else
                                <div class="avatar-initial bg-label-primary rounded-circle fs-2 fw-bold text-uppercase p-3">
                                    {{ strtoupper($initials ?: 'U') }}
                                </div>
                            @endif
                        </div>
                        <div class="flex-grow-1" style="min-width: 240px;">
                            <label class="form-label fw-bold mb-1">Profile Photo</label>
                            <input type="file" name="avatar" accept="image/jpeg,image/png,image/jpg,image/webp" class="form-control">
                            <small class="text-muted d-block mt-1">JPG, PNG or WEBP, max 2 MB — auto-resized &amp; converted to WebP.</small>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Full Name</label>
                            <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required class="form-control" maxlength="120">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email Address</label>
                            <input type="email" value="{{ $user->email ?? '' }}" disabled class="form-control">
                            <small class="text-muted">Contact your administrator to change your email.</small>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Contact Number</label>
                            <input type="text" name="contact_no" value="{{ old('contact_no', $user->contact_no ?? '') }}" class="form-control" maxlength="15">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Company Name</label>
                            <input type="text" name="company" value="{{ old('company', $user->company_name ?? '') }}" class="form-control" maxlength="150">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Country</label>
                            <input type="text" name="country" value="{{ old('country', $user->country ?? '') }}" class="form-control" maxlength="100">
                        </div>
                    </div>

                    <hr class="my-4">

                    <h6 class="fw-bold mb-3">Change Password</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" placeholder="Required for password change" autocomplete="current-password">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">New Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current" autocomplete="new-password">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="password_confirmation" class="form-control" placeholder="Repeat new password" autocomplete="new-password">
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary shadow-sm px-4">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
    @include('Admin.templates.footer')
</div>
