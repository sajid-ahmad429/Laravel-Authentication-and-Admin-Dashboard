@section('title', 'Dashboard')
@include('Admin.templates.header')


@php
    $firstName = trim(explode(' ', (string) session('name', 'there'))[0] ?? 'there');
    $canUsers = (int) config('auth.role_rank.' . strtolower(session('role', ''))) >= (int) config('auth.feature_ranks.users');
@endphp

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">

        {{-- ==================== Welcome banner ==================== --}}
        <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(120deg, #666cff 0%, #9155fd 100%);">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3 text-white p-4">
                <div>
                    <h4 class="fw-bold mb-1 text-white">Welcome back, {{ $firstName }} 👋</h4>
                    <p class="mb-0 opacity-75">Here's what's happening across your workspace today.</p>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge bg-white bg-opacity-25 text-white rounded-pill px-3 py-2">
                        <i class="mdi mdi-shield-account-outline me-1"></i>{{ ucwords(session('role')) }}
                    </span>
                    @if($canUsers)
                    <a href="{{ route('panel.users.index') }}" class="btn btn-light btn-sm d-flex align-items-center gap-1">
                        <i class="mdi mdi-account-plus-outline"></i> Manage Users
                    </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- ==================== Stat cards ==================== --}}
        <div class="row g-4 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 adt-stat-card">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted fw-semibold mb-1 small">Total Users</p>
                            <h4 class="mb-0 fw-bold">{{ number_format($stats['total']) }}</h4>
                        </div>
                        <div class="stat-icon primary"><i class="mdi mdi-account-group-outline"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 adt-stat-card">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted fw-semibold mb-1 small">Active</p>
                            <h4 class="mb-0 fw-bold">{{ number_format($stats['active']) }}</h4>
                        </div>
                        <div class="stat-icon success"><i class="mdi mdi-account-check-outline"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 adt-stat-card">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted fw-semibold mb-1 small">Inactive</p>
                            <h4 class="mb-0 fw-bold">{{ number_format($stats['inactive']) }}</h4>
                        </div>
                        <div class="stat-icon warning"><i class="mdi mdi-account-off-outline"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 adt-stat-card">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted fw-semibold mb-1 small">Awaiting Activation</p>
                            <h4 class="mb-0 fw-bold">{{ number_format($stats['unactivated']) }}</h4>
                        </div>
                        <div class="stat-icon info"><i class="mdi mdi-email-fast-outline"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            {{-- ==================== Registrations chart ==================== --}}
            <div class="col-lg-8 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold mb-0">Registrations</h5>
                            <small class="text-muted">New sign-ups over the last 14 days</small>
                        </div>
                        <span class="badge bg-label-primary rounded-pill">{{ number_format(array_sum($trend['counts'])) }} total</span>
                    </div>
                    <div class="card-body px-3">
                        <div id="registrationChart"></div>
                    </div>
                </div>
            </div>

            {{-- ==================== Role distribution ==================== --}}
            <div class="col-lg-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 pt-4 px-4">
                        <h5 class="fw-bold mb-0">Roles</h5>
                        <small class="text-muted">Users per role</small>
                    </div>
                    <div class="card-body px-4">
                        @forelse($roleBreakdown as $rb)
                            <div class="d-flex justify-content-between align-items-center py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="mdi mdi-account-circle-outline text-primary"></i>
                                    <span class="text-capitalize fw-medium">{{ str_replace('-', ' ', $rb['name']) }}</span>
                                </div>
                                <span class="badge bg-label-secondary rounded-pill">{{ number_format($rb['count']) }}</span>
                            </div>
                        @empty
                            <p class="text-muted mb-0 py-4 text-center">No roles configured yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- ==================== Recent signups ==================== --}}
            <div class="col-12 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold mb-0">Recent Sign-ups</h5>
                            <small class="text-muted">Latest accounts created</small>
                        </div>
                        @if($canUsers)
                            <a href="{{ route('panel.users.index') }}" class="btn btn-label-primary btn-sm">
                                View all <i class="mdi mdi-arrow-right ms-1"></i>
                            </a>
                        @endif
                    </div>
                    <div class="table-responsive px-2 pb-2">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr class="text-muted small text-uppercase">
                                    <th class="ps-4" style="font-size:.72rem">User</th>
                                    <th style="font-size:.72rem">Role</th>
                                    <th style="font-size:.72rem">Plan</th>
                                    <th style="font-size:.72rem">Status</th>
                                    <th class="pe-4 text-end" style="font-size:.72rem">Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentUsers as $ru)
                                    @php $rn = $ru->roleName(); @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-2">
                                                @if($ru->avatar)
                                                    <img src="{{ asset($ru->avatar) }}" class="adt-avatar" alt="">
                                                @else
                                                    <span class="adt-avatar">{{ strtoupper(substr($ru->name, 0, 1)) }}</span>
                                                @endif
                                                <div>
                                                    <div class="fw-semibold">{{ $ru->name ?: '—' }}</div>
                                                    <div class="text-muted" style="font-size:.76rem">{{ $ru->email }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-label-primary" style="text-transform:capitalize">{{ $rn ?: '—' }}</span>
                                        </td>
                                        <td class="text-capitalize">{{ $ru->plan ?: '—' }}</td>
                                        <td>
                                            @if((int) $ru->activated === 0)
                                                <span class="badge bg-label-warning"><i class="mdi mdi-email-clock-outline me-1"></i>Pending</span>
                                            @elseif($ru->status == 1)
                                                <span class="badge bg-label-success">Active</span>
                                            @else
                                                <span class="badge bg-label-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="pe-4 text-end text-muted">{{ optional($ru->created_at)->format('d M Y') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">No users yet — share the registration page to get started.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('Admin.templates.footer')
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof ApexCharts === 'undefined') return;

    var isDark = document.documentElement.classList.contains('dark-style');
    var gridColor = isDark ? '#3f425b' : '#ececf1';
    var labelColor = isDark ? '#b6b5c1' : '#8a8aa3';

    var options = {
        chart: { type: 'area', height: 290, fontFamily: 'inherit', toolbar: { show: false }, zoom: { enabled: false } },
        series: [{ name: 'Registrations', data: {!! json_encode($trend['counts']) !!} }],
        xaxis: {
            categories: {!! json_encode($trend['days']) !!},
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: { style: { colors: labelColor, fontSize: '11px' } },
            tooltip: { enabled: false }
        },
        yaxis: {
            min: 0,
            forceNiceScale: true,
            labels: { style: { colors: labelColor, fontSize: '11px' }, precision: 0 }
        },
        grid: { borderColor: gridColor, strokeDashArray: 4, padding: { left: 8, right: 8, top: -10 } },
        colors: ['#666cff'],
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [0, 95, 100] } },
        stroke: { curve: 'smooth', width: 3 },
        dataLabels: { enabled: false },
        tooltip: { theme: isDark ? 'dark' : 'light', y: { formatter: function (v) { return v + ' sign-up' + (v === 1 ? '' : 's'); } } }
    };

    var el = document.querySelector('#registrationChart');
    if (el) { new ApexCharts(el, options).render(); }
});
</script>
@endpush
