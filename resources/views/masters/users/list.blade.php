@include('Admin.templates.header')

@php
    $role = session('role');
    $roleName = !empty($role) && in_array($role, ['superadmin', 'admin']) ? $role : 'admin';
@endphp

<!-- DataTables & FormValidation Styles -->
<link rel="stylesheet"
    href="https://cdn.datatables.net/v/bs5/jszip-2.5.0/dt-1.13.4/b-2.3.6/b-colvis-2.3.6/b-html5-2.3.6/b-print-2.3.6/cr-1.6.2/fc-4.2.2/fh-3.3.2/r-2.4.1/sc-2.1.1/sp-2.1.2/sl-1.6.2/sr-1.2.2/datatables.min.css" />
<!-- SweetAlert2 CSS -->
<style>
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #dbdade !important;
        border-radius: 0.5rem !important;
        padding: 0.45rem 0.9rem !important;
        background-color: #fff !important;
        color: #6f6b7d !important;
        font-size: 0.9375rem;
    }

    .dataTables_wrapper .dataTables_filter input:focus,
    .dataTables_wrapper .dataTables_length select:focus {
        border-color: #7367f0 !important;
        box-shadow: 0 0.125rem 0.25rem rgba(115, 103, 240, 0.15) !important;
        outline: none;
    }

    table.dataTable.table-striped>tbody>tr:nth-of-type(odd) {
        background-color: #fbfbfd !important;
    }

    table.dataTable tbody tr:hover {
        background-color: #f6f5fa !important;
    }

    .dt-buttons .btn {
        border-radius: 0.375rem;
        font-weight: 500;
        padding: 0.438rem 0.875rem;
        transition: all 0.2s ease-in-out;
    }

    .dt-buttons .btn:hover {
        transform: translateY(-1px);
    }

    /* Fallback protection to clear backdrop bugs */
    body:not(.offcanvas-open) .offcanvas-backdrop {
        display: none !important;
    }

    .dataTables_filter {
        position: relative;
    }

    .dataTables_filter input {
        padding-left: 35px !important;
        /* Text icon ke upar na aaye isliye padding */
    }

    .dataTables_filter label {
        position: relative;
        display: flex;
        align-items: center;
    }

    .dataTables_filter label::before {
        content: "\F0349";
        /* Material Design Icon code for search */
        font-family: "Material Design Icons";
        position: absolute;
        left: 7px;
        /* Icon ko thoda aur andar shift kiya */
        top: 50%;
        transform: translateY(-50%);
        color: #a8b1bc;
        font-size: 18px;
        pointer-events: none;
        /* Clickable banne se rokne ke liye taaki input focus me rahe */
    }
</style>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">

        <!-- Top Analytics Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-sm-6 col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted fw-semibold mb-1">Total Users</p>
                                <div class="d-flex align-items-center">
                                    <h4 class="mb-2 me-2 fw-bold text-dark" id="statTotal">{{ $totalUsers ?? 0 }}</h4>
                                    <span class="badge bg-label-success rounded-pill mb-2">+29%</span>
                                </div>
                                <small class="text-muted">Overall registered accounts</small>
                            </div>
                            <div class="avatar avatar-lg">
                                <div class="avatar-initial bg-label-primary rounded-circle p-3">
                                    <i class="mdi mdi-account-outline mdi-24px"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted fw-semibold mb-1">Active Users</p>
                                <div class="d-flex align-items-center">
                                    <h4 class="mb-2 me-2 fw-bold text-dark" id="statActive">{{ $active ?? 0 }}</h4>
                                    <span class="badge bg-label-danger rounded-pill mb-2">-14%</span>
                                </div>
                                <small class="text-muted">Last week analytics</small>
                            </div>
                            <div class="avatar avatar-lg">
                                <div class="avatar-initial bg-label-success rounded-circle p-3">
                                    <i class="mdi mdi-account-check-outline mdi-24px"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted fw-semibold mb-1">In-Active Users</p>
                                <div class="d-flex align-items-center">
                                    <h4 class="mb-2 me-2 fw-bold text-dark" id="statInactive">{{ $inactive ?? 0 }}</h4>
                                    <span class="badge bg-label-success rounded-pill mb-2">+42%</span>
                                </div>
                                <small class="text-muted">Last week analytics</small>
                            </div>
                            <div class="avatar avatar-lg">
                                <div class="avatar-initial bg-label-warning rounded-circle p-3">
                                    <i class="mdi mdi-account-search mdi-24px"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users List Table Card -->
        <div class="card border-0 shadow-sm">
            <div
                class="card-header border-bottom bg-transparent py-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h5 class="card-title fw-bold mb-1 text-dark">User Management Dashboard</h5>
                    <p class="text-muted mb-0 small">Manage system user entries, roles, export tools and statuses
                        seamlessly.</p>
                </div>
                <div>
                    {{-- <button class="btn btn-primary shadow-sm" type="button" data-bs-toggle="offcanvas"
                        data-bs-target="#offcanvasAddUser" id="openAddUserPanel">
                        <i class="mdi mdi-plus me-1"></i> Add New User
                    </button> --}}
                </div>
            </div>

            <div class="card-body px-4 py-4">
                <div class="card-datatable table-responsive">
                    <!-- Added w-full and fixed the width property typo -->
                    <table class="datatables table table-hover align-middle border-top w-full" id="usersTable"
                        style="width: 100%;">
                        <thead class="bg-slate-50/75 backdrop-blur-md border-y border-slate-200/80 sticky top-0 z-10">
                            <tr>
                                <th
                                    class="py-3.5 px-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 hover:text-indigo-600 transition-colors cursor-pointer group">
                                    <div class="flex items-center gap-2">
                                        <span>ID</span>
                                        <span class="text-slate-400 group-hover:text-indigo-600 transition-colors">
                                            <i class="mdi mdi-swap-vertical text-sm"></i>
                                        </span>
                                    </div>
                                </th>
                                <th
                                    class="py-3.5 px-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 hover:text-indigo-600 transition-colors cursor-pointer group">
                                    <div class="flex items-center gap-2">
                                        <span>Full Name</span>
                                        <span class="text-slate-400 group-hover:text-indigo-600 transition-colors">
                                            <i class="mdi mdi-swap-vertical text-sm"></i>
                                        </span>
                                    </div>
                                </th>
                                <th
                                    class="py-3.5 px-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500 hover:text-indigo-600 transition-colors cursor-pointer group">
                                    <div class="flex items-center gap-2">
                                        <span>Email Address</span>
                                        <span class="text-slate-400 group-hover:text-indigo-600 transition-colors">
                                            <i class="mdi mdi-swap-vertical text-sm"></i>
                                        </span>
                                    </div>
                                </th>
                                <th
                                    class="py-3.5 px-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Role</th>
                                <th
                                    class="py-3.5 px-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Plan</th>
                                <th
                                    class="py-3.5 px-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Country</th>
                                <th
                                    class="py-3.5 px-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Status</th>
                                <th
                                    class="py-3.5 px-4 text-center text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <!-- Offcanvas to add/edit user -->
            <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddUser"
                aria-labelledby="offcanvasAddUserLabel">
                <div class="offcanvas-header border-bottom bg-light">
                    <h5 id="offcanvasAddUserLabel" class="offcanvas-title fw-bold text-dark">Add New User Entity</h5>
                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                        aria-label="Close"></button>
                </div>
                <div class="offcanvas-body mx-0 flex-grow-0 h-100 p-4">
                    <form class="add-new-user pt-0" id="addNewUserForm" action="{{ url($roleName . '/add-new-users') }}"
                        method="post">
                        @csrf
                        <input type="hidden" id="userId" value="0" name="user_id" />

                        <div class="form-floating form-floating-outline mb-4 fv-row">
                            <input type="text" class="form-control required" data-bind="name" id="userFullname"
                                placeholder="John Doe" name="userFullname" aria-label="John Doe" />
                            <label for="userFullname">Full Name <span class="text-danger">*</span></label>
                        </div>

                        <div class="form-floating form-floating-outline mb-4 fv-row">
                            <input type="text" id="userEmail" class="form-control required" data-bind="email"
                                placeholder="Email" aria-label="john.doe@example.com" name="userEmail" />
                            <label for="userEmail">Email <span class="text-danger">*</span></label>
                        </div>

                        <div class="form-floating form-floating-outline mb-4 fv-row">
                            <input type="text" id="userContact" class="form-control phone-mask required"
                                maxlength="10" data-bind="mobileNumber" placeholder="Contact Number"
                                aria-label="john.doe@example.com" name="userContact"
                                onkeypress="return isNumberKey(event, this)" />
                            <label for="userContact">Contact <span class="text-danger">*</span></label>
                        </div>

                        <div class="form-floating form-floating-outline mb-4 fv-row">
                            <input type="text" id="companyName" class="form-control required"
                                placeholder="Web Developer" aria-label="jdoe1" name="companyName" />
                            <label for="companyName">Company <span class="text-danger">*</span></label>
                        </div>

                        <div class="form-floating form-floating-outline mb-4 fv-row">
                            <select id="country" name="country" class="select2 form-select select2Required">
                                <option value="">Select Country</option>
                                <option value="Australia">Australia</option>
                                <option value="Bangladesh">Bangladesh</option>
                                <option value="Belarus">Belarus</option>
                                <option value="Brazil">Brazil</option>
                                <option value="Canada">Canada</option>
                                <option value="China">China</option>
                                <option value="France">France</option>
                                <option value="Germany">Germany</option>
                                <option value="India">India</option>
                                <option value="Indonesia">Indonesia</option>
                                <option value="Israel">Israel</option>
                                <option value="Italy">Italy</option>
                                <option value="Japan">Japan</option>
                                <option value="Korea">Korea, Republic of</option>
                                <option value="Mexico">Mexico</option>
                                <option value="Philippines">Philippines</option>
                                <option value="Russia">Russian Federation</option>
                                <option value="South Africa">South Africa</option>
                                <option value="Thailand">Thailand</option>
                                <option value="Turkey">Turkey</option>
                                <option value="Ukraine">Ukraine</option>
                                <option value="United Arab Emirates">United Arab Emirates</option>
                                <option value="United Kingdom">United Kingdom</option>
                                <option value="United States">United States</option>
                            </select>
                            <label for="country">Country <span class="text-danger">*</span></label>
                        </div>

                        <div class="form-floating form-floating-outline mb-4 fv-row">
                            <select id="userRole" class="form-select required" name="user-role">
                                <option value="">Select Roles</option>
                                <option value="subscriber">Subscriber</option>
                                <option value="editor">Editor</option>
                                <option value="maintainer">Maintainer</option>
                                <option value="author">Author</option>
                                <option value="admin">Admin</option>
                            </select>
                            <label for="userRole">User Role <span class="text-danger">*</span></label>
                        </div>

                        <div class="form-floating form-floating-outline mb-4 fv-row">
                            <select id="userPlan" class="form-select required" name="user-plan">
                                <option value="">Select Plans</option>
                                <option value="basic">Basic</option>
                                <option value="enterprise">Enterprise</option>
                                <option value="company">Company</option>
                                <option value="team">Team</option>
                            </select>
                            <label for="userPlan">Select Plan <span class="text-danger">*</span></label>
                        </div>

                        <div class="d-flex gap-3 mt-4">
                            <button type="submit" class="btn btn-primary flex-fill shadow-sm"
                                id="submitFormBtn">Save Entity</button>
                            <button type="reset" class="btn btn-outline-secondary"
                                data-bs-dismiss="offcanvas">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @include('Admin.templates.footer')
</div>

<!-- Scripts Dependencies -->
<script
    src="https://cdn.datatables.net/v/bs5/jszip-2.5.0/dt-1.13.4/b-2.3.6/b-colvis-2.3.6/b-html5-2.3.6/b-print-2.3.6/cr-1.6.2/fc-4.2.2/fh-3.3.2/r-2.4.1/sc-2.1.1/sp-2.1.2/sl-1.6.2/sr-1.2.2/datatables.min.js">
</script>
<!-- Tailwind CSS CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
    $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        const table = $('#usersTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: {
                details: {
                    display: $.fn.dataTable.Responsive.display.modal({
                        header: function(row) {
                            var data = row.data();
                            return 'Details of ' + data['full_name'];
                        }
                    }),
                    type: 'column',
                    renderer: function(api, rowIdx, columns) {
                        var data = $.map(columns, function(col, i) {
                            return col.title !== '' ?
                                '<tr data-dt-row="' + col.rowIndex + '" data-dt-column="' +
                                col.columnIndex + '">' +
                                '<td>' + col.title + ':</td> ' +
                                '<td>' + col.data + '</td>' +
                                '</tr>' : '';
                        }).join('');
                        return data ? $('<table class="table"/><tbody />').append(data) : false;
                    }
                }
            },
            deferRender: true,
            pageLength: 10,
            order: [
                [0, 'desc']
            ],
            dom: '<"row mx-2"' +
                '<"col-md-2"<"me-3"l>>' +
                '<"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0 gap-3"fB>>' +
                '>t' +
                '<"row mx-2"' +
                '<"col-sm-12 col-md-6"i>' +
                '<"col-sm-12 col-md-6"p>' +
                '>',
            // 🛠️ YEH RAHI UPDATED LANGUAGE CONFIGURATION (Icon aur Filtered count ke sath)
            language: {
                sLengthMenu: 'Show _MENU_',
                search: '',
                searchPlaceholder: 'Search..',
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoFiltered: "(filtered from _MAX_ total entries)"
            },
            ajax: {
                url: "{{ route($roleName . '.users.data') }}",
                type: "POST",
                dataSrc: function(json) {
                    if (json.totalActiveRecods) $('#statActive').text(json.totalActiveRecods);
                    if (json.totalInActiveRecods) $('#statInactive').text(json.totalInActiveRecods);
                    return json.data;
                }
            },
            columns: [{
                    data: 'id',
                    name: 'id',
                    orderable: false,
                    render: function(data, type, full, meta) {
                        return meta.row + 1 + meta.settings._iDisplayStart;
                    }
                },
                {
                    data: 'full_name',
                    name: 'full_name',
                    render: function(data) {
                        return '<span class="fw-semibold text-dark">' + data + '</span>';
                    }
                },
                {
                    data: 'email',
                    name: 'email'
                },
                {
                    data: 'role',
                    name: 'role',
                    render: function(data, type, full, meta) {
                        var $role = full['role'];
                        var roleBadgeObj = {
                            Subscriber: '<i class="mdi mdi-account-outline mdi-20px text-primary me-2"></i>',
                            Author: '<i class="mdi mdi-cog-outline mdi-20px text-warning me-2"></i>',
                            Maintainer: '<i class="mdi mdi-chart-donut mdi-20px text-success me-2"></i>',
                            Editor: '<i class="mdi mdi-pencil-outline mdi-20px text-info me-2"></i>',
                            Admin: '<i class="mdi mdi-laptop mdi-20px text-danger me-2"></i>'
                        };
                        return "<span class='text-truncate d-flex align-items-center'>" +
                            (roleBadgeObj[$role] || '') + $role + '</span>';
                    }
                },
                {
                    data: 'current_plan',
                    name: 'current_plan'
                },
                {
                    data: 'country',
                    name: 'country'
                },
                {
                    data: 'status',
                    name: 'status',
                    render: function(data, type, full) {
                        var id = full['id'];
                        var encodedId = btoa(id);
                        var encodedType = btoa('users');

                        if (data == 1) {
                            return '<button class="badge bg-label-success btn btn-sm border-0" onclick="updateStatus(\'' +
                                encodedId + '\', 0, \'' + encodedType +
                                '\', \'users\')">Active</button>';
                        } else {
                            return '<button class="badge bg-label-secondary btn btn-sm border-0" onclick="updateStatus(\'' +
                                encodedId + '\', 1, \'' + encodedType +
                                '\', \'users\')">Inactive</button>';
                        }
                    }
                },
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                    render: function(data, type, full) {
                        var userId = full['id'];
                        var encodedUserId = btoa(userId);
                        var activated = full['activated'];
                        var activateButton = "";

                        if (activated == 0) {
                            activateButton +=
                                '<a href="javascript:;" class="dropdown-item" title="Send activation link" ' +
                                'onclick="ActivateUser(\'' + btoa('users') + '\', \'' +
                                encodedUserId + '\', \'' + encodedUserId + '\', \'' + btoa(2) +
                                '\')">' +
                                '<i class="mdi mdi-shield-check me-2"></i><span>Send Activation Link</span></a>';
                        }

                        return (
                            '<div class="d-inline-block text-nowrap">' +
                            '<button class="btn btn-sm btn-icon btn-text-secondary rounded-pill dropdown-toggle hide-arrow" data-bs-toggle="dropdown">' +
                            '<i class="mdi mdi-dots-vertical mdi-20px"></i></button>' +
                            '<div class="dropdown-menu dropdown-menu-end m-0">' +
                            '<a href="javascript:;" class="dropdown-item edit-user-btn" data-id="' +
                            encodedUserId +
                            '" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddUser">' +
                            '<i class="mdi mdi-pencil-outline me-2"></i><span>Edit</span></a>' +
                            '<a href="javascript:;" class="dropdown-item text-danger" onclick="updateStatus(\'' +
                            encodedUserId + '\', 2, \'' + btoa('users') + '\', \'' + btoa(
                                2) + '\')">' +
                            '<i class="mdi mdi-delete-outline me-2"></i><span>Delete</span></a>' +
                            activateButton +
                            '</div>' +
                            '</div>'
                        );
                    }
                }
            ],
            buttons: [{
                    extend: 'collection',
                    className: 'btn btn-label-secondary dropdown-toggle me-3',
                    text: '<i class="mdi mdi-export-variant me-1"></i> <span class="d-none d-sm-inline-block">Export</span>',
                    buttons: [{
                            extend: 'print',
                            text: '<i class="mdi mdi-printer-outline me-1"></i>Print',
                            className: 'dropdown-item',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4, 5, 6]
                            }
                        },
                        {
                            extend: 'csv',
                            text: '<i class="mdi mdi-file-document-outline me-1"></i>Csv',
                            className: 'dropdown-item',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4, 5, 6]
                            }
                        },
                        {
                            extend: 'excel',
                            text: '<i class="mdi mdi-file-excel-outline me-1"></i>Excel',
                            className: 'dropdown-item',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4, 5, 6]
                            }
                        },
                        {
                            extend: 'pdf',
                            text: '<i class="mdi mdi-file-pdf-box me-1"></i>Pdf',
                            className: 'dropdown-item',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4, 5, 6]
                            }
                        },
                        {
                            extend: 'copy',
                            text: '<i class="mdi mdi-content-copy me-1"></i>Copy',
                            className: 'dropdown-item',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4, 5, 6]
                            }
                        }
                    ]
                },
                {
                    text: '<i class="mdi mdi-plus me-0 me-sm-1"></i><span class="d-none d-sm-inline-block">Add User</span>',
                    className: 'add-new btn btn-primary',
                    attr: {
                        'data-bs-toggle': 'offcanvas',
                        'data-bs-target': '#offcanvasAddUser'
                    }
                }
            ]
        });

        // Form Validation Setup
        const formElement = document.getElementById('addNewUserForm');
        const fvInstance = FormValidation.formValidation(formElement, {
            fields: {
                userFullname: {
                    validators: {
                        notEmpty: {
                            message: 'The full name field is required'
                        },
                        regexp: {
                            regexp: /^[a-zA-Z\s-]+$/,
                            message: 'Only alphabets and spaces allowed'
                        }
                    }
                },
                userEmail: {
                    validators: {
                        notEmpty: {
                            message: 'The email address is required'
                        },
                        emailAddress: {
                            message: 'Please enter a valid email structure'
                        }
                    }
                },
                userContact: {
                    validators: {
                        notEmpty: {
                            message: 'The contact configuration string is required'
                        },
                        stringLength: {
                            min: 10,
                            max: 10,
                            message: 'Must contain exactly 10 digits'
                        },
                        digits: {
                            message: 'Only integer digits allowed'
                        }
                    }
                },
                companyName: {
                    validators: {
                        notEmpty: {
                            message: 'The company name is required'
                        }
                    }
                },
                country: {
                    validators: {
                        notEmpty: {
                            message: 'Please select a country'
                        }
                    }
                },
                'user-role': {
                    validators: {
                        notEmpty: {
                            message: 'Please select a user role'
                        }
                    }
                },
                'user-plan': {
                    validators: {
                        notEmpty: {
                            message: 'Please select a subscription plan'
                        }
                    }
                }
            },
            plugins: {
                bootstrap5: new FormValidation.plugins.Bootstrap5({
                    rowSelector: '.fv-row',
                    eleInvalidClass: 'is-invalid',
                    eleValidClass: 'is-valid'
                }),
                autoFocus: new FormValidation.plugins.AutoFocus()
            }
        });

        function openPanel(title = "Add New User", btnText = "Save Entity") {
            $('#offcanvasAddUserLabel').text(title);
            $('#submitFormBtn').text(btnText);
            const bsOffcanvas = new bootstrap.Offcanvas('#offcanvasAddUser');
            bsOffcanvas.show();

        }

        function closePanel() {
            const offcanvasEl = document.getElementById('offcanvasAddUser');
            const bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
            if (bsOffcanvas) {
                bsOffcanvas.hide();
            }

            // Forcefully clear out lingering backdrops and scroll lock
            $('.offcanvas-backdrop').remove();
            $('body').removeClass('offcanvas-open').css({
                'overflow': '',
                'padding-right': ''
            });

            fvInstance.resetForm(true);
            formElement.reset();
            $('#userId').val('0');
        }

        // Handle bootstrap native hidden event as well to ensure backdrop clears up completely
        const offcanvasElement = document.getElementById('offcanvasAddUser');
        offcanvasElement.addEventListener('hidden.bs.offcanvas', function() {
            $('.offcanvas-backdrop').remove();
            $('body').removeClass('offcanvas-open').css({
                'overflow': '',
                'padding-right': ''
            });
            fvInstance.resetForm(true);
            formElement.reset();
            $('.select2').val([]).trigger('change');
            $('#userId').val('0');
        });

        $('#openAddUserPanel').on('click', function() {
            formElement.reset();
            $('#userId').val('0');
            $('.select2').val([]).trigger('change');
            fvInstance.resetForm(true);
            openPanel("Add New User", "Save Entity");
        });

        $('#submitFormBtn').on('click', function(e) {
            e.preventDefault();

            fvInstance.validate().then(function(status) {
                if (status === 'Valid') {
                    // Turant button disable aur spinner/loading text daal dein
                    const $btn = $('#submitFormBtn');
                    $btn.prop('disabled', true).addClass('opacity-50');
                    const originalText = $btn.html();
                    $btn.html(
                        '<span class="spinner-border spinner-border-sm me-1"></span> Processing...'
                        );

                    $.ajax({
                        url: "{{ route($roleName . '.users.store') }}",
                        type: "POST",
                        data: $(formElement).serialize(),
                        success: function(response) {
                            if (response.status == 1) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: response.message,
                                    timer: 1500, // Timer thoda kam kar sakte hain (1.5 sec)
                                    showConfirmButton: false
                                });
                                closePanel();

                                // Table ko background mein reload hone dein
                                setTimeout(function() {
                                    table.ajax.reload(null, false);
                                }, 100);
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    text: response.message
                                });
                            }
                        },
                        error: function(xhr) {
                            // Aapka 422 validation error code yahan as it is rahega...
                        },
                        complete: function() {
                            // Button ko wapas original state mein laayein
                            $btn.prop('disabled', false).removeClass('opacity-50')
                                .html(originalText);
                        }
                    });
                }
            });
        });

        $('#usersTable').on('click', '.edit-user-btn', function() {
            const encodedId = $(this).data('id');
            $.ajax({
                url: "{{ route($roleName . '.users.details') }}",
                type: "POST",
                data: {
                    id: encodedId
                },
                success: function(data) {
                    fvInstance.resetForm(true);
                    $('#userId').val(data.id);
                    $('#userFullname').val(data.name);
                    $('#userEmail').val(data.email);
                    $('#userContact').val(data.contact_no);
                    $('#companyName').val(data.company_name);
                    $('#userRole').val(data.roles);
                    $('#userPlan').val(data.plan);
                    $('#country').val(data.country).trigger('change');

                    openPanel("Modify User Profile", "Update Configuration");
                },
                error: function() {
                    Swal.fire('Error', 'Could not load user data profile safely.', 'error');
                }
            });
        });

        $('#usersTable').on('click', '.btn-trash', function() {
            const encodedId = $(this).data('id');
            Swal.fire({
                title: 'Are you sure?',
                text: "Do you want to drop this user record into trash logs?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#7367f0',
                cancelButtonColor: '#ea5455',
                confirmButtonText: 'Yes, trash it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    executeTrashToggle(encodedId, 1);
                }
            });
        });

        $('#usersTable').on('click', '.btn-restore', function() {
            const encodedId = $(this).data('id');
            Swal.fire({
                title: 'Restore User?',
                text: "Do you want to restore this user back to active registries?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28c76f',
                cancelButtonColor: '#ea5455',
                confirmButtonText: 'Yes, restore it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    executeTrashToggle(encodedId, 0);
                }
            });
        });

        function executeTrashToggle(targetId, actionCode) {
            $.ajax({
                url: "{{ route($roleName . '.users.toggleTrash') }}",
                type: "POST",
                data: {
                    id: targetId,
                    action_type: actionCode
                },
                success: function(res) {
                    if (res.status === 1) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        table.ajax.reload(null, false);
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }
            });
        }
    });
</script>
