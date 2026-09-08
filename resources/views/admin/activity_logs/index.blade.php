@section('title', 'Activity Logs')
@include('Admin.templates.header')


<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h4 class="fw-bold mb-1">Activity Logs</h4>
                <p class="text-muted mb-0">Complete audit trail of every administrative action.</p>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('panel.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Activity Logs</li>
                </ol>
            </nav>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="adt-toolbar" id="logToolbar">
                <div class="adt-search">
                    <i class="mdi mdi-magnify adt-search-icon"></i>
                    <input type="search" id="logSearch" placeholder="Search user, action, table, IP…" autocomplete="off"
                        aria-label="Search activity logs">
                </div>
                <div class="adt-filters" id="severityChips">
                    <button type="button" class="adt-chip active" data-severity="">All</button>
                    <button type="button" class="adt-chip" data-severity="info">Info</button>
                    <button type="button" class="adt-chip" data-severity="warning">Warning</button>
                    <button type="button" class="adt-chip" data-severity="danger">Danger</button>
                    <button type="button" class="adt-chip" data-severity="critical">Critical</button>
                </div>
            </div>
            <div class="px-2 pb-2" id="logsTable"></div>
        </div>
    </div>
    @include('Admin.templates.footer')
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var severityTones = {
        info: 'primary', warning: 'warning', danger: 'danger', critical: 'danger'
    };
    var severityIcons = {
        info: 'mdi-information-outline', warning: 'mdi-alert-outline',
        danger: 'mdi-alert-octagon-outline', critical: 'mdi-alert-decagram-outline'
    };

    var table = new AdminTable('#logsTable', {
        url: '{{ route('panel.activity.data') }}',
        initialSort: [{ column: 'id', dir: 'desc' }],
        searchInput: '#logSearch',
        filters: { severity: '' },
        pageSize: 15,
        exportName: 'activity-logs',
        columns: [
            { title: '#', field: 'id', width: 80, formatter: AdminUI.fmt.text() },
            { title: 'Actor', field: 'user_name', minWidth: 190, formatter: function (cell) {
                var d = cell.getRow().getData();
                var name = d.user_name || 'System';
                var initials = name.split(/\s+/).map(function (p) { return p.charAt(0); }).join('').slice(0, 2).toUpperCase();
                return '<div class="adt-user-cell">' +
                    '<span class="adt-avatar">' + AdminUI.escape(initials || 'S') + '</span>' +
                    '<div class="adt-user-meta"><div class="adt-user-name">' + AdminUI.escape(name) + '</div>' +
                    (d.user_email ? '<div class="adt-user-email">' + AdminUI.escape(d.user_email) + '</div>' : '') +
                    '</div></div>';
            }, headerSort: false },
            { title: 'Action', field: 'action_type', width: 130, formatter: function (cell) {
                var v = (cell.getValue() || 'INFO').toLowerCase();
                var tone = severityTones[v] || 'dark';
                var icon = severityIcons[v] || 'mdi-pencil-outline';
                return AdminUI.fmt.badge(cell.getValue() || 'INFO', tone, icon);
            } },
            { title: 'Target', field: 'table_name', width: 140, formatter: function (cell) {
                var d = cell.getRow().getData();
                if (!d.table_name || d.table_name === '-') return '<span class="text-muted">—</span>';
                var label = d.table_name + (d.record_id ? ' #' + d.record_id : '');
                return '<span class="text-muted">' + AdminUI.escape(label) + '</span>';
            } },
            { title: 'Details', field: 'log_text', minWidth: 280, formatter: AdminUI.fmt.text(), headerSort: false },
            { title: 'IP Address', field: 'ip_address', width: 140, formatter: AdminUI.fmt.text(), headerSort: false },
            { title: 'When', field: 'logged_at', width: 160, formatter: AdminUI.fmt.date }
        ]
    });

    document.querySelectorAll('#severityChips .adt-chip').forEach(function (chip) {
        chip.addEventListener('click', function () {
            document.querySelectorAll('#severityChips .adt-chip').forEach(function (c) { c.classList.remove('active'); });
            chip.classList.add('active');
            table.setFilter('severity', chip.getAttribute('data-severity'));
        });
    });
});
</script>
@endpush
