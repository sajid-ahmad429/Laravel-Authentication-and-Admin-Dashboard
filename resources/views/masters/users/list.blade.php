@section('title', 'User Management')
@include('Admin.templates.header')

@php
    $isSuperadmin = strtolower(session('role', '')) === 'superadmin';
@endphp


@push('styles')
<style>
    .adt-stat-card { transition: transform .18s ease, box-shadow .18s ease; }
    .adt-stat-card:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(48,44,80,.08) !important; }
    .users-offcanvas .form-label { font-size: .8125rem; font-weight: 600; color: #6f6b7d; margin-bottom: .35rem; }
    .users-offcanvas .req::after { content: " *"; color: #ea5455; }
    .users-offcanvas .invalid-feedback { display: block; }
</style>
@endpush

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h4 class="fw-bold mb-1">User Management</h4>
                <p class="text-muted mb-0">Create, manage and audit every account in your workspace.</p>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('panel.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Users</li>
                </ol>
            </nav>
        </div>

        {{-- ======================= Stat cards ======================= --}}
        <div class="row g-4 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card adt-stat-card h-100 border-0 shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted fw-semibold mb-1 small">Total Users</p>
                            <h4 class="mb-0 fw-bold" id="statTotal">{{ $stats['total'] }}</h4>
                        </div>
                        <div class="stat-icon primary"><i class="mdi mdi-account-group-outline"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card adt-stat-card h-100 border-0 shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted fw-semibold mb-1 small">Active</p>
                            <h4 class="mb-0 fw-bold" id="statActive">{{ $stats['active'] }}</h4>
                        </div>
                        <div class="stat-icon success"><i class="mdi mdi-account-check-outline"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card adt-stat-card h-100 border-0 shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted fw-semibold mb-1 small">Inactive</p>
                            <h4 class="mb-0 fw-bold" id="statInactive">{{ $stats['inactive'] }}</h4>
                        </div>
                        <div class="stat-icon warning"><i class="mdi mdi-account-off-outline"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card adt-stat-card h-100 border-0 shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted fw-semibold mb-1 small">Awaiting Activation</p>
                            <h4 class="mb-0 fw-bold" id="statUnactivated">{{ $stats['unactivated'] }}</h4>
                        </div>
                        <div class="stat-icon info"><i class="mdi mdi-email-fast-outline"></i></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ======================= Table card ======================= --}}
        <div class="card border-0 shadow-sm">
            <div class="adt-toolbar" id="usersToolbar">
                <div class="adt-search">
                    <i class="mdi mdi-magnify adt-search-icon"></i>
                    <input type="search" id="userSearch" placeholder="Search name, email, company, country…"
                        autocomplete="off" aria-label="Search users">
                </div>

                <div class="adt-filters" id="statusChips">
                    <button type="button" class="adt-chip active" data-status="">All</button>
                    <button type="button" class="adt-chip" data-status="1">Active</button>
                    <button type="button" class="adt-chip" data-status="0">Inactive</button>
                    <select id="roleFilter" class="adt-chip" style="padding-right:1.6rem" aria-label="Filter by role">
                        <option value="">All roles</option>
                        @foreach($roles as $roleKey => $roleLabel)
                            @if($isSuperadmin || $roleKey !== 'superadmin')
                                <option value="{{ $roleKey }}">{{ ucwords($roleKey) }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div class="adt-actions">
                    <div class="dropdown">
                        <button class="adt-chip dropdown-toggle" data-bs-toggle="dropdown" type="button">
                            <i class="mdi mdi-export-variant me-1"></i>Export
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><button class="dropdown-item" type="button" id="exportCsv"><i class="mdi mdi-file-document-outline me-2"></i>CSV</button></li>
                            <li><button class="dropdown-item" type="button" id="exportJson"><i class="mdi mdi-code-json me-2"></i>JSON</button></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <button class="dropdown-item" type="button" id="trashToggle">
                                    <i class="mdi mdi-delete-outline me-2"></i><span id="trashToggleLabel">View Trash</span>
                                </button>
                            </li>
                        </ul>
                    </div>
                    <button class="btn btn-primary d-flex align-items-center gap-1" type="button" id="addUserBtn">
                        <i class="mdi mdi-plus"></i> <span>Add User</span>
                    </button>
                </div>
            </div>

            <div class="px-2 pb-2" id="usersTable"></div>
        </div>
    </div>
    @include('Admin.templates.footer')
</div>

{{-- ======================= Add / Edit offcanvas ======================= --}}
<div class="offcanvas offcanvas-end users-offcanvas" tabindex="-1" id="offcanvasUserForm" aria-labelledby="userFormTitle">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold" id="userFormTitle">Add New User</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-4">
        <form id="userForm" novalidate>
            <input type="hidden" name="user_id" id="userId" value="0">

            <div class="mb-3">
                <label class="form-label req" for="userFullname">Full Name</label>
                <input type="text" class="form-control @error('userFullname') is-invalid @enderror" id="userFullname"
                    name="userFullname" placeholder="e.g. Jane Doe" maxlength="120" required>
                <div class="invalid-feedback" data-error-for="userFullname"></div>
            </div>

            <div class="mb-3">
                <label class="form-label req" for="userEmail">Email Address</label>
                <input type="email" class="form-control" id="userEmail" name="userEmail"
                    placeholder="jane@example.com" maxlength="255" required>
                <div class="invalid-feedback" data-error-for="userEmail"></div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="userContact">Contact Number</label>
                <input type="tel" class="form-control" id="userContact" name="userContact"
                    placeholder="+1 555 000 1234" maxlength="15">
                <div class="invalid-feedback" data-error-for="userContact"></div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="companyName">Company</label>
                <input type="text" class="form-control" id="companyName" name="companyName" maxlength="150">
                <div class="invalid-feedback" data-error-for="companyName"></div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="country">Country</label>
                <input type="text" class="form-control" id="country" name="country" maxlength="100" list="countryList">
                <datalist id="countryList">
                    @foreach(['Australia','Brazil','Canada','China','France','Germany','India','Indonesia','Italy','Japan','Mexico','Netherlands','Philippines','Russia','Singapore','South Africa','Spain','Thailand','Turkey','United Arab Emirates','United Kingdom','United States'] as $c)
                        <option value="{{ $c }}"></option>
                    @endforeach
                </datalist>
                <div class="invalid-feedback" data-error-for="country"></div>
            </div>

            <div class="mb-3">
                <label class="form-label req" for="userRole">Role</label>
                <select class="form-select" id="userRole" name="user-role" required>
                    <option value="">Select role…</option>
                    @foreach($roles as $roleKey => $roleLabel)
                        @if($isSuperadmin || $roleKey !== 'superadmin')
                            <option value="{{ $roleKey }}">{{ $roleLabel }}</option>
                        @endif
                    @endforeach
                </select>
                <div class="form-text">Accounts can only be assigned roles at or below your own level.</div>
                <div class="invalid-feedback" data-error-for="user-role"></div>
            </div>

            <div class="mb-4">
                <label class="form-label" for="userPlan">Plan</label>
                <select class="form-select" id="userPlan" name="user-plan">
                    <option value="">No plan</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan }}">{{ ucwords($plan) }}</option>
                    @endforeach
                </select>
                <div class="invalid-feedback" data-error-for="user-plan"></div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill" id="submitUserBtn">Save User</button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">Cancel</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var dataUrl   = {{ route('panel.users.data') === '' ? "''" : "'" . route('panel.users.data') . "'" }};
    var storeUrl  = '{{ route('panel.users.store') }}';
    var detailUrl = '{{ route('panel.users.details') }}';
    var trashUrl  = '{{ route('panel.users.trash') }}';
    var statusUrl = '{{ route('panel.users.status') }}';
    var resendUrl = '{{ route('panel.users.resend') }}';

    // ------------------------------------------------------------ table
    var inTrash = false;

    function updateStats(stats) {
        if (!stats) return;
        document.getElementById('statTotal').textContent = stats.total || 0;
        document.getElementById('statActive').textContent = stats.active || 0;
        document.getElementById('statInactive').textContent = stats.inactive || 0;
        document.getElementById('statUnactivated').textContent = stats.unactivated || 0;
    }

    function statusToggleAction(d, cell) {
        var next = d.status === 1 ? 0 : 1;
        AdminUI.confirm({
            title: (next === 1 ? 'Activate' : 'Deactivate') + ' user?',
            text: (d.name || 'This user') + ' will be ' + (next === 1 ? 'able to sign in again.' : 'blocked from signing in.'),
            icon: 'question',
            confirmText: 'Yes, ' + (next === 1 ? 'activate' : 'deactivate'),
            confirmColor: next === 1 ? '#28c76f' : '#ff9f43'
        }).then(function (ok) {
            if (!ok) return;
            AdminUI.ajax(statusUrl, { id: d.id, status: next })
                .then(function (res) {
                    AdminUI.toast(res.message, 'success');
                    table.reload(false);
                })
                .catch(function (err) { AdminUI.toast(err.message, 'danger'); });
        });
    }

    var columns = [
        { title: 'User', field: 'name', minWidth: 230, formatter: AdminUI.fmt.avatarCell, sorter: function(a, b, aRow, bRow){ return (aRow.getData().name||'').localeCompare(bRow.getData().name||''); } },
        { title: 'Role', field: 'role', width: 150, formatter: AdminUI.fmt.role.cell, headerSort: false },
        { title: 'Plan', field: 'plan', width: 130, formatter: AdminUI.fmt.simpleBadge('info', 'mdi-credit-card-outline') },
        { title: 'Country', field: 'country', width: 130, formatter: AdminUI.fmt.text() },
        { title: 'Status', field: 'status', width: 120, hozAlign: 'center', headerSort: false, formatter: function(cell){ return AdminUI.fmt.statusToggle(cell, table, statusToggleAction); } },
        { title: 'Joined', field: 'created_at', width: 120, formatter: AdminUI.fmt.date },
        {
            title: 'Actions', field: 'actions', width: 140, hozAlign: 'center', headerSort: false, print: false,
            formatter: AdminUI.fmt.actions([
                {
                    title: 'Edit', icon: 'mdi-pencil-outline',
                    when: function (d) { return d.can_edit; },
                    onClick: function (d) { openEditor(d.id); }
                },
                {
                    title: 'Resend activation', icon: 'mdi-email-fast-outline',
                    when: function (d) { return d.activated === 0 && d.can_edit; },
                    onClick: function (d) {
                        AdminUI.confirm({
                            title: 'Send activation link?',
                            text: 'A fresh activation link will be emailed to ' + (d.email || 'this user') + '.',
                            icon: 'info', confirmText: 'Send link'
                        }).then(function (ok) {
                            if (!ok) return;
                            AdminUI.ajax(resendUrl, { id: d.id })
                                .then(function (res) { AdminUI.toast(res.message, res.status === 1 ? 'success' : 'danger'); table.reload(false); })
                                .catch(function (err) { AdminUI.toast(err.message, 'danger'); });
                        });
                    }
                },
                {
                    title: function(){ return inTrash ? 'Restore' : 'Trash'; },
                    danger: true,
                    icon: function(){ return inTrash ? 'mdi-restore' : 'mdi-trash-can-outline'; },
                    when: function (d) { return inTrash ? d.can_edit : d.can_trash; },
                    onClick: function (d) {
                        var toTrash = !inTrash;
                        AdminUI.confirm({
                            title: toTrash ? 'Move to trash?' : 'Restore user?',
                            text: toTrash
                                ? (d.name || 'This user') + ' will be moved to trash and blocked from signing in.'
                                : (d.name || 'This user') + ' will be restored to the active list.',
                            confirmText: toTrash ? 'Yes, trash it' : 'Yes, restore',
                            confirmColor: toTrash ? '#ea5455' : '#28c76f'
                        }).then(function (ok) {
                            if (!ok) return;
                            AdminUI.ajax(trashUrl, { id: d.id, action_type: toTrash ? 1 : 0 })
                                .then(function (res) {
                                    AdminUI.toast(res.message, 'success');
                                    table.reload(false);
                                })
                                .catch(function (err) { AdminUI.toast(err.message, 'danger'); });
                        });
                    }
                }
            ])
        }
    ];

    var table = new AdminTable('#usersTable', {
        url: dataUrl,
        columns: columns,
        initialSort: [{ column: 'id', dir: 'desc' }],
        pageSize: 10,
        searchInput: '#userSearch',
        filters: { trash: 0, status: '', role: '' },
        exportName: 'users',
        onData: function (payload) { updateStats(payload.stats); }
    });

    // ---------------------------------------------------- toolbar wiring
    document.querySelectorAll('#statusChips .adt-chip[data-status]').forEach(function (chip) {
        chip.addEventListener('click', function () {
            document.querySelectorAll('#statusChips .adt-chip[data-status]').forEach(function (c) { c.classList.remove('active'); });
            chip.classList.add('active');
            table.setFilter('status', chip.getAttribute('data-status'));
        });
    });

    document.getElementById('roleFilter').addEventListener('change', function (e) {
        table.setFilter('role', e.target.value);
    });

    document.getElementById('trashToggle').addEventListener('click', function () {
        inTrash = !inTrash;
        table.setFilter('trash', inTrash ? 1 : 0);
        document.getElementById('trashToggleLabel').textContent = inTrash ? 'Exit Trash' : 'View Trash';
        AdminUI.toast(inTrash ? 'Showing trash — use the restore action to recover users.' : 'Showing active users.', 'info');
    });

    document.getElementById('exportCsv').addEventListener('click', function () { table.export('csv'); });
    document.getElementById('exportJson').addEventListener('click', function () { table.export('json'); });

    // ---------------------------------------------------- offcanvas form
    var formEl = document.getElementById('userForm');
    var offcanvasEl = document.getElementById('offcanvasUserForm');
    var offcanvas = new bootstrap.Offcanvas(offcanvasEl);

    function openCreate() {
        formEl.reset();
        document.getElementById('userId').value = '0';
        document.getElementById('userFormTitle').textContent = 'Add New User';
        document.getElementById('submitUserBtn').textContent = 'Save User';
        clearErrors();
        offcanvas.show();
    }

    function openEditor(id) {
        clearErrors();
        AdminUI.ajax(detailUrl, { id: id }).then(function (res) {
            var u = res.data || {};
            document.getElementById('userId').value = u.id;
            document.getElementById('userFullname').value = u.name || '';
            document.getElementById('userEmail').value = u.email || '';
            document.getElementById('userContact').value = u.contact_no || '';
            document.getElementById('companyName').value = u.company_name || '';
            document.getElementById('country').value = u.country || '';
            document.getElementById('userRole').value = u.role || '';
            document.getElementById('userPlan').value = (u.plan || '').toLowerCase();
            document.getElementById('userFormTitle').textContent = 'Edit User';
            document.getElementById('submitUserBtn').textContent = 'Update User';
            offcanvas.show();
        }).catch(function (err) { AdminUI.toast(err.message, 'danger'); });
    }

    document.getElementById('addUserBtn').addEventListener('click', openCreate);

    function clearErrors() {
        formEl.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
        formEl.querySelectorAll('.invalid-feedback').forEach(function (el) { el.textContent = ''; });
    }

    function showErrors(errors) {
        clearErrors();
        Object.keys(errors || {}).forEach(function (key) {
            var input = formEl.querySelector('[name="' + key + '"]');
            var feedback = formEl.querySelector('[data-error-for="' + key + '"]');
            if (input) input.classList.add('is-invalid');
            if (feedback) feedback.textContent = (errors[key] || ['Invalid value'])[0];
        });
    }

    formEl.addEventListener('submit', function (e) {
        e.preventDefault();
        var btn = document.getElementById('submitUserBtn');
        var original = btn.textContent;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving…';

        var payload = {
            user_id: document.getElementById('userId').value,
            userFullname: document.getElementById('userFullname').value,
            userEmail: document.getElementById('userEmail').value,
            userContact: document.getElementById('userContact').value,
            companyName: document.getElementById('companyName').value,
            country: document.getElementById('country').value,
            'user-role': document.getElementById('userRole').value,
            'user-plan': document.getElementById('userPlan').value
        };

        AdminUI.ajax(storeUrl, payload)
            .then(function (res) {
                AdminUI.toast(res.message, 'success');
                offcanvas.hide();
                table.reload(false);
            })
            .catch(function (err) {
                if (err.status === 422 && err.errors) showErrors(err.errors);
                else AdminUI.toast(err.message, 'danger');
            })
            .finally(function () {
                btn.disabled = false;
                btn.textContent = original;
            });
    });
});
</script>
@endpush
