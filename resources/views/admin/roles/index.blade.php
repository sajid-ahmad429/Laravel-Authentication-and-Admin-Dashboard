@include('Admin.templates.header')

@php
    $role = session('role');
    $roleName = !empty($role) && in_array($role, ['superadmin', 'admin']) ? $role : 'admin';
@endphp

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
    }

    .dataTables_filter label {
        position: relative;
        display: flex;
        align-items: center;
    }

    .dataTables_filter label::before {
        content: "\F0349";
        font-family: "Material Design Icons";
        position: absolute;
        left: 7px;
        top: 50%;
        transform: translateY(-50%);
        color: #a8b1bc;
        font-size: 18px;
        pointer-events: none;
    }
</style>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header border-bottom bg-transparent py-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h5 class="card-title fw-bold mb-1 text-dark">Roles Management</h5>
                    <p class="text-muted mb-0 small">Define system roles and assign capabilities across the platform.</p>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible mx-4 mt-3" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card-body px-4 py-4">
                <div class="card-datatable table-responsive">
                    <table class="datatables table table-hover align-middle border-top w-full" id="rolesTable" style="width: 100%;">
                        <thead class="bg-slate-50/75 backdrop-blur-md border-y border-slate-200/80 sticky top-0 z-10">
                            <tr>
                                <th>ID</th>
                                <th>Role Name</th>
                                <th>Assigned Permissions</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
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

<script>
    $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $('#rolesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route($roleName . '.roles.data') }}",
                type: 'POST'
            },
            columns: [
                { data: 'id', name: 'id' },
                { data: 'name', name: 'name' },
                { data: 'permissions', name: 'permissions', orderable: false, searchable: false },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            dom: '<"row align-items-center mx-2"' +
                '<"col-md-2"l>' +
                '<"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0 gap-3"fB>>' +
                '>t' +
                '<"row mx-2"' +
                '<"col-sm-12 col-md-6"i>' +
                '<"col-sm-12 col-md-6"p>' +
                '>',
            language: {
                sLengthMenu: 'Show _MENU_',
                search: '',
                searchPlaceholder: 'Search..'
            },
            buttons: [
                {
                    extend: 'collection',
                    className: 'btn btn-label-secondary dropdown-toggle me-3',
                    text: '<i class="mdi mdi-export-variant me-1"></i> <span class="d-none d-sm-inline-block">Export</span>',
                    buttons: [
                        {
                            extend: 'print',
                            text: '<i class="mdi mdi-printer-outline me-1"></i>Print',
                            className: 'dropdown-item',
                            exportOptions: { columns: [0, 1, 2] }
                        },
                        {
                            extend: 'csv',
                            text: '<i class="mdi mdi-file-document-outline me-1"></i>Csv',
                            className: 'dropdown-item',
                            exportOptions: { columns: [0, 1, 2] }
                        },
                        {
                            extend: 'excel',
                            text: '<i class="mdi mdi-file-excel-outline me-1"></i>Excel',
                            className: 'dropdown-item',
                            exportOptions: { columns: [0, 1, 2] }
                        },
                        {
                            extend: 'pdf',
                            text: '<i class="mdi mdi-file-pdf-box me-1"></i>Pdf',
                            className: 'dropdown-item',
                            exportOptions: { columns: [0, 1, 2] }
                        },
                        {
                            extend: 'copy',
                            text: '<i class="mdi mdi-content-copy me-1"></i>Copy',
                            className: 'dropdown-item',
                            exportOptions: { columns: [0, 1, 2] }
                        }
                    ]
                },
                {
                    text: '<i class="mdi mdi-plus me-0 me-sm-1"></i><span class="d-none d-sm-inline-block">ADD ROLE</span>',
                    className: 'add-new btn btn-primary rounded-3 shadow-sm',
                    attr: {
                        'data-bs-toggle': 'offcanvas',
                        'data-bs-target': '#offcanvasAddRole'
                    }
                }
            ]
        });
    });
</script>
