@section('title', 'Roles & Access Control')
@include('Admin.templates.header')


<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h4 class="fw-bold mb-1">Roles &amp; Access Control</h4>
                <p class="text-muted mb-0">Define roles and attach granular permissions to them.</p>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('panel.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Roles</li>
                </ol>
            </nav>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="adt-toolbar" id="rolesToolbar">
                <div class="adt-search">
                    <i class="mdi mdi-magnify adt-search-icon"></i>
                    <input type="search" id="roleSearch" placeholder="Search roles…" autocomplete="off"
                        aria-label="Search roles">
                </div>
                <div class="adt-actions">
                    <button class="btn btn-primary d-flex align-items-center gap-1" type="button" id="addRoleBtn">
                        <i class="mdi mdi-plus"></i> <span>New Role</span>
                    </button>
                </div>
            </div>
            <div class="px-2 pb-2" id="rolesTable"></div>
        </div>
    </div>
    @include('Admin.templates.footer')
</div>

{{-- ======================= Create role offcanvas ======================= --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRoleForm" aria-labelledby="roleFormTitle">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold" id="roleFormTitle">Create Role</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-4">
        <form id="roleForm" action="{{ route('panel.roles.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="roleName">Role Name <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text">/</span>
                    <input type="text" class="form-control" id="roleName" name="name"
                        placeholder="e.g. content-writer" pattern="[a-z0-9\-]+" maxlength="50" required>
                </div>
                <div class="form-text">Lowercase letters, numbers and dashes only.</div>
            </div>

            <div class="mb-4">
                <label class="form-label">Permissions</label>
                <div class="border rounded p-3" style="max-height: 320px; overflow-y: auto;">
                    @forelse($permissions as $permission)
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="permissions[]"
                                id="perm-{{ $permission->id }}" value="{{ $permission->name }}">
                            <label class="form-check-label" for="perm-{{ $permission->id }}">
                                {{ ucwords(str_replace(['-', '_'], ' ', $permission->name)) }}
                            </label>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No permissions defined yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">Create Role</button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">Cancel</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var deleteUrl = '{{ route('panel.roles.destroy') }}';

    var table = new AdminTable('#rolesTable', {
        url: '{{ route('panel.roles.data') }}',
        initialSort: [{ column: 'id', dir: 'desc' }],
        searchInput: '#roleSearch',
        exportName: 'roles',
        columns: [
            { title: 'Role', field: 'label', minWidth: 180, formatter: function (cell) {
                var d = cell.getRow().getData();
                var icon = d.protected ? 'mdi-shield-crown-outline text-danger' : 'mdi-account-circle-outline';
                return '<div class="d-flex align-items-center gap-2">' +
                    '<span class="stat-icon primary" style="width:2.2rem;height:2.2rem;font-size:1rem;border-radius:.5rem">' +
                    '<i class="mdi ' + icon + '"></i></span>' +
                    '<div><div class="fw-semibold">' + AdminUI.escape(d.label) + '</div>' +
                    '<div class="text-muted" style="font-size:.76rem">/' + AdminUI.escape(d.name) + '</div></div></div>';
            } },
            { title: 'Users', field: 'users_count', width: 100, hozAlign: 'center', formatter: function (cell) {
                var v = cell.getValue() || 0;
                return AdminUI.fmt.badge(v + ' user' + (v === 1 ? '' : 's'), 'secondary', 'mdi-account-multiple-outline');
            } },
            { title: 'Permissions', field: 'permissions', minWidth: 260, formatter: function (cell) {
                var perms = cell.getValue() || [];
                if (!perms.length) return '<span class="text-muted">No permissions</span>';
                var shown = perms.slice(0, 4).map(function (p) {
                    return '<span class="adt-badge primary" style="text-transform:capitalize">' + AdminUI.escape(p) + '</span>';
                }).join(' ');
                var extra = perms.length > 4
                    ? ' <span class="adt-badge dark">+' + (perms.length - 4) + ' more</span>' : '';
                return '<div class="d-flex flex-wrap gap-1">' + shown + extra + '</div>';
            }, headerSort: false },
            { title: 'Actions', field: 'actions', width: 90, hozAlign: 'center', headerSort: false,
              formatter: AdminUI.fmt.actions([
                  {
                      title: 'Delete role', icon: 'mdi-trash-can-outline', danger: true,
                      when: function (d) { return !d.protected; },
                      onClick: function (d) {
                          AdminUI.confirm({
                              title: 'Delete role “' + d.label + '”?',
                              text: 'This action cannot be undone.',
                              confirmText: 'Delete', confirmColor: '#ea5455'
                          }).then(function (ok) {
                              if (!ok) return;
                              AdminUI.ajax(deleteUrl, { id: d.id })
                                  .then(function (res) { AdminUI.toast(res.message, 'success'); table.reload(false); })
                                  .catch(function (err) { AdminUI.toast(err.message, 'danger'); });
                          });
                      }
                  }
              ]) }
        ]
    });

    document.getElementById('addRoleBtn').addEventListener('click', function () {
        new bootstrap.Offcanvas(document.getElementById('offcanvasRoleForm')).show();
    });
});
</script>
@endpush
