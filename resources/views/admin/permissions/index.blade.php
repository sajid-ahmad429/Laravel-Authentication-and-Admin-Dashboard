@include('Admin.templates.header')

@php
    $role = session('role');
    $roleName = !empty($role) && in_array($role, ['superadmin', 'admin']) ? $role : 'admin';
@endphp

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header border-bottom bg-transparent py-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h5 class="card-title fw-bold mb-1 text-dark">Permissions Management</h5>
                    <p class="text-muted mb-0 small">Create and manage access rights across system components.</p>
                </div>
                <div>
                    <button class="btn btn-primary shadow-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddPermission">
                        <i class="mdi mdi-plus me-1"></i> Add Permission
                    </button>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible mx-4 mt-3" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card-body px-4 py-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle border-top w-full">
                        <thead class="bg-light">
                            <tr>
                                <th>ID</th>
                                <th>Permission Name</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($permissions as $permission)
                            <tr>
                                <td class="fw-bold">{{ $permission->id }}</td>
                                <td class="fw-semibold text-dark">{{ $permission->name }}</td>
                                <td class="text-center">
                                    <form action="{{ route($roleName . '.permissions.destroy', $permission->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this permission?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="mdi mdi-delete-outline me-1"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Offcanvas drawer to add permission -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddPermission" aria-labelledby="offcanvasAddPermissionLabel">
            <div class="offcanvas-header border-bottom bg-light">
                <h5 id="offcanvasAddPermissionLabel" class="offcanvas-title fw-bold text-dark">Add New Permission</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body mx-0 flex-grow-0 h-100 p-4">
                <form action="{{ route($roleName . '.permissions.store') }}" method="POST">
                    @csrf
                    <div class="form-floating form-floating-outline mb-4">
                        <input type="text" class="form-control" id="permNameInput" name="name" placeholder="Permission Name (e.g. edit users)" required />
                        <label for="permNameInput">Permission Name <span class="text-danger">*</span></label>
                    </div>

                    <div class="d-flex gap-3 mt-4">
                        <button type="submit" class="btn btn-primary flex-fill shadow-sm">Save Permission</button>
                        <button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
    @include('Admin.templates.footer')
</div>
