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
                    <h5 class="card-title fw-bold mb-1 text-dark">Activity Audit Trail</h5>
                    <p class="text-muted mb-0 small">Real-time system events, change tracking, and security logs.</p>
                </div>
            </div>

            <div class="card-body px-4 py-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle border-top w-full" id="activityLogsTable">
                        <thead class="bg-light">
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Table</th>
                                <th>Log Message</th>
                                <th>IP Address</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                            <tr>
                                <td class="fw-bold">{{ $log->id }}</td>
                                <td>{{ $log->user_name ?? 'System' }}</td>
                                <td><span class="badge bg-label-info text-uppercase">{{ $log->action_type }}</span></td>
                                <td><code>{{ $log->table_name }}</code></td>
                                <td class="small">{{ $log->log_text }}</td>
                                <td><small class="text-muted">{{ $log->ip_address }}</small></td>
                                <td><small class="text-muted">{{ $log->created_at }}</small></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No audit activity logs recorded yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if(method_exists($logs, 'links'))
                <div class="mt-3">
                    {{ $logs->links() }}
                </div>
                @endif
            </div>
        </div>

    </div>
    @include('Admin.templates.footer')
</div>
