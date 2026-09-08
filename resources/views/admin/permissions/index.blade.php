@section('title', 'Permissions')
@include('Admin.templates.header')


<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h4 class="fw-bold mb-1">Permissions</h4>
                <p class="text-muted mb-0">Granular capabilities that can be attached to any role.</p>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('panel.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Permissions</li>
                </ol>
            </nav>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="adt-toolbar" id="permToolbar">
                <div class="adt-search">
                    <i class="mdi mdi-magnify adt-search-icon"></i>
                    <input type="search" id="permSearch" placeholder="Search permissions…" autocomplete="off"
                        aria-label="Search permissions">
                </div>
                <div class="adt-actions">
                    <button class="btn btn-primary d-flex align-items-center gap-1" type="button" id="addPermBtn">
                        <i class="mdi mdi-plus"></i> <span>New Permission</span>
                    </button>
                </div>
            </div>
            <div class="px-2 pb-2" id="permissionsTable"></div>
        </div>
    </div>
    @include('Admin.templates.footer')
</div>

{{-- ======================= Create permission modal ======================= --}}
<div class="modal fade" id="permModal" tabindex="-1" aria-labelledby="permModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="permForm" action="{{ route('panel.permissions.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="permModalTitle">New Permission</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label" for="permName">Permission Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="permName" name="name"
                            placeholder="e.g. manage invoices" pattern="[a-z0-9\-\s]+" maxlength="100" required>
                        <div class="form-text">Lowercase words, e.g. <code>manage invoices</code>, <code>publish articles</code>.</div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Permission</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var deleteUrl = '{{ route('panel.permissions.destroy') }}';

    var table = new AdminTable('#permissionsTable', {
        url: '{{ route('panel.permissions.data') }}',
        initialSort: [{ column: 'id', dir: 'desc' }],
        searchInput: '#permSearch',
        exportName: 'permissions',
        columns: [
            { title: 'Permission', field: 'name', minWidth: 220, formatter: function (cell) {
                var v = cell.getValue();
                return '<span class="fw-semibold">' + AdminUI.escape(v) + '</span>';
            } },
            { title: 'Guard', field: 'guard_name', width: 130, formatter: AdminUI.fmt.simpleBadge('dark', 'mdi-shield-lock-outline') },
            { title: 'Created', field: 'created', width: 140, formatter: AdminUI.fmt.date },
            { title: 'Actions', field: 'actions', width: 90, hozAlign: 'center', headerSort: false,
              formatter: AdminUI.fmt.actions([
                  {
                      title: 'Delete permission', icon: 'mdi-trash-can-outline', danger: true,
                      onClick: function (d) {
                          AdminUI.confirm({
                              title: 'Delete “' + d.name + '”?',
                              text: 'Permissions in use by roles cannot be deleted.',
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

    var modalEl = document.getElementById('permModal');
    var modal = new bootstrap.Modal(modalEl);
    document.getElementById('addPermBtn').addEventListener('click', function () { modal.show(); });
});
</script>
@endpush
