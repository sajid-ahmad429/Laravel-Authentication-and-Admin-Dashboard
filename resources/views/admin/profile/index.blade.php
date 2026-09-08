@include('admin.templates.header')

@php
    $role = session('role');
    $roleName = !empty($role) && in_array($role, ['superadmin', 'admin']) ? $role : 'admin';
@endphp

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="card border-0 shadow-sm max-w-3xl mx-auto">
            <div class="card-header border-bottom bg-transparent py-3">
                <h5 class="card-title fw-bold mb-1 text-dark">Profile Settings</h5>
                <p class="text-muted mb-0 small">Update your personal information and account avatar.</p>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible mx-4 mt-3" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card-body p-4">
                <form action="{{ route($roleName . '.profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="d-flex align-items-center gap-4 mb-4">
                        <div class="avatar avatar-xl">
                            @if(!empty($user->avatar))
                                <img src="{{ asset($user->avatar) }}" alt="Avatar" class="rounded-circle object-cover" width="80" height="80">
                            @else
                                <div class="avatar-initial bg-label-primary rounded-circle fs-3 font-bold text-uppercase p-3">
                                    {{ substr($user->name ?? 'U', 0, 2) }}
                                </div>
                            @endif
                        </div>
                        <div>
                            <label class="form-label fw-bold mb-1">Profile Photo</label>
                            <input type="file" name="avatar" accept="image/jpeg,image/png,image/jpg,image/webp" class="form-control form-control-sm">
                            <small class="text-muted d-block mt-1">Allowed: JPG, PNG, WEBP (Max 2MB). Auto-resized & converted to WebP.</small>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Full Name</label>
                            <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email Address</label>
                            <input type="email" value="{{ $user->email ?? '' }}" disabled class="form-control bg-light">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Contact Number</label>
                            <input type="text" name="contact_no" value="{{ old('contact_no', $user->contact_no ?? '') }}" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Company Name</label>
                            <input type="text" name="company" value="{{ old('company_name', $user->company_name ?? '') }}" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Country</label>
                            <input type="text" name="country" value="{{ old('country', $user->country ?? '') }}" class="form-control">
                        </div>
                    </div>

                    <hr class="my-4">

                    <h6 class="fw-bold mb-3">Change Password</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">New Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm password">
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary shadow-sm px-4">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
    @include('admin.templates.footer')
</div>
