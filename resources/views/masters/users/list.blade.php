@include('Admin.templates.header')

@php
    $role = session('role'); // Fetch the role from the session
    $roleName = '';

    if (!empty($role)) {
        switch ($role) {
            case 'superadmin':
                $roleName = 'superadmin';
                break;
            case 'admin':
                $roleName = 'admin';
                break;
            default:
                $roleName = 'admin'; // Fallback to safe admin or change as per your routes
                break;
        }
    } else {
        $roleName = 'admin'; // Fallback if empty
    }
@endphp

<!-- Content wrapper -->
<div class="content-wrapper">
    <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-sm-6 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="me-1">
                                <p class="text-heading mb-2">Total Users</p>
                                <div class="d-flex align-items-center">
                                    <h4 class="mb-2 me-1 display-6">{{ $totalUsers ?? 0 }}</h4>
                                    <p class="text-success mb-2">(+29%)</p>
                                </div>
                                <p class="mb-0">Total Users</p>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial bg-label-primary rounded">
                                    <div class="mdi mdi-account-outline mdi-24px"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="me-1">
                                <p class="text-heading mb-2">Active Users</p>
                                <div class="d-flex align-items-center">
                                    <h4 class="mb-2 me-1 display-6">{{ $active ?? 0 }}</h4>
                                    <p class="text-danger mb-2">(-14%)</p>
                                </div>
                                <p class="mb-0">Last week analytics</p>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial bg-label-success rounded">
                                    <div class="mdi mdi-account-check-outline mdi-24px"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="me-1">
                                <p class="text-heading mb-2">In-Active Users</p>
                                <div class="d-flex align-items-center">
                                    <h4 class="mb-2 me-1 display-6">{{ $inactive ?? 0 }}</h4>
                                    <p class="text-success mb-2">(+42%)</p>
                                </div>
                                <p class="mb-0">Last week analytics</p>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial bg-label-warning rounded">
                                    <div class="mdi mdi-account-search mdi-24px"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users List Table -->
        <div class="card">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-3">Search Filter</h5>
                <div class="d-flex justify-content-between align-items-center row py-3 gap-3 gap-md-0">
                    <div class="col-md-4 user_role"></div>
                    <div class="col-md-4 user_plan"></div>
                    <div class="col-md-4 user_status"></div>
                </div>
            </div>

            <div class="card-datatable table-responsive">
    <table class="datatables table" id="datable_userList">
        <thead class="table-light">
            <tr>
                <th></th> <!-- Target 0: Control / Responsive icon column -->
                <th>ID</th> <!-- Target 1 -->
                <th>User</th> <!-- Target 2 -->
                <th>Role</th> <!-- Target 3 -->
                <th>Plan</th> <!-- Target 4 -->
                <th>Country</th> <!-- Target 5 -->
                <th>Status</th> <!-- Target 6 -->
                <th>Actions</th> <!-- Target 7: Edit & Delete buttons -->
            </tr>
        </thead>
    </table>
</div>
            

            <!-- Offcanvas to add/edit user -->
            <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddUser"
                aria-labelledby="offcanvasAddUserLabel">
                <div class="offcanvas-header border-bottom">
                    <h5 id="offcanvasAddUserLabel" class="offcanvas-title">Add / Edit User</h5>
                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                        aria-label="Close"></button>
                </div>
                <div class="offcanvas-body mx-0 flex-grow-0 h-100">
                    <form class="add-new-user pt-0" id="addNewUserForm" action="{{ url($roleName . '/add-new-users') }}"
                        method="post">
                        @csrf
                        <input type="hidden" id="Id" value="0" name="user_id" />

                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control required" data-bind="name" id="add-user-fullname"
                                placeholder="John Doe" name="userFullname" aria-label="John Doe" />
                            <label for="add-user-fullname">Full Name</label>
                        </div>

                        <div class="form-floating form-floating-outline mb-4">
                            <input type="email" id="add-user-email" class="form-control required" data-bind="email"
                                placeholder="john.doe@example.com" aria-label="john.doe@example.com" name="userEmail" />
                            <label for="add-user-email">Email</label>
                        </div>

                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" id="add-user-contact" class="form-control phone-mask required"
                                maxlength="10" data-bind="mobileNumber" placeholder="Contact Number"
                                aria-label="Contact Number" name="userContact"
                                onkeypress="return isNumberKey(event, this)" />
                            <label for="add-user-contact">Contact</label>
                        </div>

                        <div class="form-floating form-floating-outline mb-4">
                            <input type="text" id="add-user-company" class="form-control required"
                                placeholder="Web Developer" aria-label="Company Name" name="companyName" />
                            <label for="add-user-company">Company</label>
                        </div>

                        <div class="form-floating form-floating-outline mb-4">
                            <select id="country" name="country" class="select2 form-select select2Required">
                                <option value="">Select Country</option>
                                <option value="Australia">Australia</option>
                                <option value="Bangladesh">Bangladesh</option>
                                <option value="India">India</option>
                                <option value="United States">United States</option>
                            </select>
                            <label for="country">Country</label>
                        </div>

                        <div class="form-floating form-floating-outline mb-4">
                            <select id="user-role" class="form-select required" name="user-role">
                                <option value="">Select Roles</option>
                                <option value="subscriber">Subscriber</option>
                                <option value="editor">Editor</option>
                                <option value="maintainer">Maintainer</option>
                                <option value="author">Author</option>
                                <option value="admin">Admin</option>
                            </select>
                            <label for="user-role">User Role</label>
                        </div>

                        <div class="form-floating form-floating-outline mb-4">
                            <select id="user-plan" class="form-select required" name="user-plan">
                                <option value="">Select Plans</option>
                                <option value="basic">Basic</option>
                                <option value="enterprise">Enterprise</option>
                                <option value="company">Company</option>
                                <option value="team">Team</option>
                            </select>
                            <label for="user-plan">Select Plan</label>
                        </div>

                        <button type="button" class="btn btn-primary me-sm-3 me-1 data-submit"
                            onclick="validate_form('addNewUserForm');">Submit</button>
                        <button type="reset" class="btn btn-outline-secondary"
                            data-bs-dismiss="offcanvas">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- / Content -->

    @include('Admin.templates.footer')

   <script>
   var datatableUserTable;

$(function () {
    datatableUserTable = $('#datable_userList').DataTable({
        "processing": true,
        "serverSide": true,
        "iDisplayLength": 25,
        info: true,
        "bStateSave": true,
        "order": [[1, 'desc']],
        "responsive": true,
        "lengthMenu": [
            [10, 25, 50, 100, 200, 500, 600, 750],
            [10, 25, 50, 100, 200, 500, 600, 750]
        ],
        fixedHeader: true,
        dom: '<"row mx-2"<"col-md-2"<"me-3"l>><"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0 gap-3"fB>>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        buttons: [
            {
                text: '<i class="ti ti-plus me-0 me-sm-1 ti-xs"></i><span class="d-none d-sm-inline-block">Add New User</span>',
                className: 'create-new btn btn-primary waves-effect waves-light',
                attr: {
                    'data-bs-toggle': 'offcanvas',
                    'data-bs-target': '#offcanvasAddUser'
                }
            },
            {
                extend: 'collection',
                className: 'btn btn-label-secondary dropdown-toggle me-3 waves-effect waves-light',
                text: '<i class="ti ti-screen-share me-1 ti-xs"></i>Export',
                buttons: [
                    {
                        extend: 'print',
                        className: 'dropdown-item',
                        text: '<i class="ti ti-printer me-1"></i>Print',
                        exportOptions: {
                            columns: [0, 2, 3, 4, 5, 6],
                            format: {
                                body: function (data, row, column, node) {
                                    if (column === 0) return $(node).text().trim();
                                    if (column === 1) return $(node).find('.fw-semibold').text().trim();
                                    if (column === 2) return $(node).text().trim();
                                    if (column === 3) return $(node).text().trim();
                                    if (column === 4) return $(node).text().trim();
                                    if (column === 5) return $(node).text().trim();
                                    return data;
                                }
                            }
                        }
                    },
                    {
                        extend: 'csv',
                        className: 'dropdown-item',
                        text: '<i class="ti ti-file-text me-1"></i>Csv',
                        exportOptions: {
                            columns: [0, 2, 3, 4, 5, 6],
                            format: {
                                body: function (data, row, column, node) {
                                    if (column === 0) return $(node).text().trim();
                                    if (column === 1) return $(node).find('.fw-semibold').text().trim();
                                    if (column === 2) return $(node).text().trim();
                                    if (column === 3) return $(node).text().trim();
                                    if (column === 4) return $(node).text().trim();
                                    if (column === 5) return $(node).text().trim();
                                    return data;
                                }
                            }
                        }
                    },
                    {
                        extend: 'excel',
                        className: 'dropdown-item',
                        text: '<i class="ti ti-file-spreadsheet me-1"></i>Excel',
                        exportOptions: {
                            columns: [0, 2, 3, 4, 5, 6],
                            format: {
                                body: function (data, row, column, node) {
                                    if (column === 0) return $(node).text().trim();
                                    if (column === 1) {
                                        var str = $(node).find('.fw-semibold').text().trim();
                                        str += "  " + $(node).find('.emaildivjs').text().trim();
                                        return str;
                                    }
                                    if (column === 2) return $(node).text().trim();
                                    if (column === 3) return $(node).text().trim();
                                    if (column === 4) return $(node).text().trim();
                                    if (column === 5) return $(node).text().trim();
                                    return data;
                                }
                            }
                        }
                    },
                    {
                        extend: 'pdf',
                        className: 'dropdown-item',
                        text: '<i class="ti ti-file-description me-1"></i>Pdf',
                        exportOptions: {
                            columns: [0, 2, 3, 4, 5, 6],
                            format: {
                                body: function (data, row, column, node) {
                                    if (column === 0) return $(node).text().trim();
                                    if (column === 1) return $(node).find('.fw-semibold').text().trim();
                                    if (column === 2) return $(node).text().trim();
                                    if (column === 3) return $(node).text().trim();
                                    if (column === 4) return $(node).text().trim();
                                    if (column === 5) return $(node).text().trim();
                                    return data;
                                }
                            }
                        }
                    },
                    {
                        extend: 'copy',
                        className: 'dropdown-item',
                        text: '<i class="ti ti-copy me-1"></i>Copy'
                    }
                ]
            }
        ],
        "ajax": {
            "url": "{{ route($roleName . '.users.getTableData') }}",
            "type": "POST",
            "data": function (data) {
                data.id = '';
                if (typeof acftkname !== 'undefined') {
                    data[acftkname] = acftknhs;
                }
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        },
        "drawCallback": function (settings) {
            var response = settings.json;
            if(response) {
                $(".totalInActiveParts").html(response.totalInActiveRecods || 0);
                $(".totalActiveParts").html(response.totalActiveRecods || 0);
                $(".totalParts").html(response.recordsTotal || 0);
                $("#pendingVerifyCount").html(response.recordsPending || 0);
            }
        },
        columns: [
            { data: 'id' },
            { data: 'id' },       // Target 1
            { data: 'full_name' },// Target 2
            { data: 'role' },     // Target 3
            { data: 'current_plan' },// Target 4
            { data: 'country' },  // Target 5
            { data: 'status' },   // Target 6
            { data: 'id' }        // Target 7 (Actions)
        ],
        "columnDefs": [
            {
                className: 'control',
                orderable: false,
                searchable: false,
                responsivePriority: 2,
                targets: 0,
                render: function() {
                    return '';
                }
            },
            {
                targets: 1,
                render: function(data, type, full) {
                    return '<span class="text-body font-weight-bold">#' + full.id + '</span>';
                }
            },
            {
                targets: 2,
                render: function(data, type, full) {
                    var name = full.full_name || '';
                    var email = full.email || '';
                    var initials = name.match(/\b\w/g) || [];
                    initials = ((initials.shift() || '') + (initials.pop() || '')).toUpperCase();

                    var stateNum = Math.floor(Math.random() * 6);
                    var states = ['success', 'danger', 'warning', 'info', 'primary', 'secondary'];
                    var colorState = states[stateNum];

                    var output = '<div class="avatar-wrapper"><div class="avatar avatar-sm me-3"><span class="avatar-initial rounded-circle bg-label-' + colorState + '">' + initials + '</span></div></div>';

                    return '<div class="d-flex justify-content-start align-items-center user-name">' +
                        output +
                        '<div class="d-flex flex-column">' +
                        '<a href="javascript:;" class="text-body text-truncate fw-medium"><span class="fw-medium text-heading">' + name + '</span></a>' +
                        '<small class="text-muted emaildivjs">' + email + '</small>' +
                        '</div>' +
                        '</div>';
                }
            },
            {
                targets: 3,
                render: function(data, type, full) {
                    var role = full.role;
                    var roleBadgeObj = {
                        'Admin': '<span class="badge badge-center rounded-pill bg-label-danger w-h-30 me-2"><i class="ti ti-device-laptop ti-xs"></i></span>',
                        'Author': '<span class="badge badge-center rounded-pill bg-label-warning w-h-30 me-2"><i class="ti ti-settings ti-xs"></i></span>',
                        'Maintainer': '<span class="badge badge-center rounded-pill bg-label-success w-h-30 me-2"><i class="ti ti-chart-pie ti-xs"></i></span>',
                        'Subscriber': '<span class="badge badge-center rounded-pill bg-label-primary w-h-30 me-2"><i class="ti ti-user ti-xs"></i></span>'
                    };
                    return '<span class="text-truncate d-flex align-items-center text-heading">' + (roleBadgeObj[role] || '') + role + '</span>';
                }
            },
            {
                targets: 4,
                render: function(data, type, full) {
                    return '<span class="fw-medium text-heading">' + (full.current_plan || '-') + '</span>';
                }
            },
            {
                targets: 5,
                render: function(data, type, full) {
                    return '<span class="text-heading">' + (full.country || '-') + '</span>';
                }
            },
            {
                targets: 6,
                render: function(data, type, full) {
                    var statusObj = {
                        1: { title: 'Active', class: 'bg-label-success' },
                        0: { title: 'Inactive', class: 'bg-label-secondary' }
                    };
                    var status = full.status;
                    if (typeof statusObj[status] === 'undefined') {
                        return data;
                    }
                    return '<span class="badge ' + statusObj[status].class + ' text-capitalize">' + statusObj[status].title + '</span>';
                }
            },
            {
                targets: 7,
                orderable: false,
                searchable: false,
                render: function(data, type, full) {
                    return `
                        <div class="d-inline-flex align-items-center gap-1">
                            <button type="button" class="btn btn-sm btn-icon btn-text-primary edit-user" data-id="${full.id}" title="Edit">
                                <i class="ti ti-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-icon btn-text-danger delete-user" data-id="${full.id}" title="Delete">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        language: {
            sLengthMenu: '_MENU_',
            search: '',
            searchPlaceholder: 'Search User..',
            paginate: {
                next: '<i class="ti ti-chevron-right ti-sm"></i>',
                previous: '<i class="ti ti-chevron-left ti-sm"></i>'
            }
        }
    });

    $('#btn-filter').click(function () {
        datatableUserTable.ajax.reload();
    });
    
    $('#btn-reset').click(function () {
        $('#form-filter')[0].reset();
        datatableUserTable.ajax.reload();
    });
});
</script>
</div>
