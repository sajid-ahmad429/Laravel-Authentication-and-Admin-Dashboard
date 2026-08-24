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
                    <h5 class="card-title fw-bold mb-1 text-dark">Roles Management</h5>
                    <p class="text-muted mb-0 small">Define system roles and assign capabilities across the platform.</p>
                </div>
                <div>
                    <button class="btn btn-primary shadow-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddRole">
                        <i class="mdi mdi-plus me-1"></i> Add New Role
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
                                <th>Role Name</th>
                                <th>Assigned Permissions</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($roles as $roleItem)
                            <tr>
                                <td class="fw-bold">{{ $roleItem->id }}</td>
                                <td class="fw-semibold text-uppercase text-primary">{{ $roleItem->name }}</td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @forelse($roleItem->permissions as $perm)
                                            <span class="badge bg-label-primary rounded-pill">{{ $perm->name }}</span>
                                        @empty
                                            <span class="text-muted small">No permissions assigned</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="text-center">
                                    <form action="{{ route($roleName . '.roles.destroy', $roleItem->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this role?');">
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

        <!-- Offcanvas drawer to add new role -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddRole" aria-labelledby="offcanvasAddRoleLabel">
            <div class="offcanvas-header border-bottom bg-light">
                <h5 id="offcanvasAddRoleLabel" class="offcanvas-title fw-bold text-dark">Add New Role</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body mx-0 flex-grow-0 h-100 p-4">
                <form action="{{ route($roleName . '.roles.store') }}" method="POST">
                    @csrf
                    <div class="form-floating form-floating-outline mb-4">
                        <input type="text" class="form-control" id="roleNameInput" name="name" placeholder="Role Name (e.g. editor)" required />
                        <label for="roleNameInput">Role Name <span class="text-danger">*</span></label>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold mb-2">Assign Permissions</label>
                        <div class="row g-2">
                            @foreach($permissions as $permission)
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="perm_{{ $permission->id }}">
                                    <label class="form-check-label text-capitalize" for="perm_{{ $permission->id }}">
                                        {{ $permission->name }}
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="d-flex gap-3 mt-4">
                        <button type="submit" class="btn btn-primary flex-fill shadow-sm">Save Role</button>
                        <button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
    @include('Admin.templates.footer')
</div>
