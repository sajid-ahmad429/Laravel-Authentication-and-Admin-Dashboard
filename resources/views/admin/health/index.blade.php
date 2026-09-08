@section('title', 'System Health')
@include('Admin.templates.header')


<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h4 class="fw-bold mb-1">System Health</h4>
                <p class="text-muted mb-0">Live status of infrastructure and background workers.</p>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('panel.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Health</li>
                </ol>
            </nav>
        </div>

        {{-- Core services --}}
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex items-center justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Database Connection</span>
                            <div class="fw-bold mt-1">{{ ucfirst(config('database.default')) }}</div>
                        </div>
                        @if($healthMetrics['database'])
                            <span class="adt-badge success"><i class="mdi mdi-check-circle-outline"></i>Healthy</span>
                        @else
                            <span class="adt-badge danger"><i class="mdi mdi-alert-circle-outline"></i>Unhealthy</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Cache Driver</span>
                            <div class="fw-bold mt-1">{{ ucfirst(config('cache.default')) }}</div>
                        </div>
                        @if($healthMetrics['cache'])
                            <span class="adt-badge success"><i class="mdi mdi-check-circle-outline"></i>Healthy</span>
                        @else
                            <span class="adt-badge danger"><i class="mdi mdi-alert-circle-outline"></i>Unhealthy</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Metrics --}}
        <div class="row g-4 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-semibold text-uppercase">Pending Jobs</span>
                                <div class="h4 fw-bold mb-0 mt-1 {{ ($healthMetrics['pending_jobs'] ?? 0) > 100 ? 'text-warning' : '' }}">
                                    {{ number_format($healthMetrics['pending_jobs']) }}
                                </div>
                            </div>
                            <div class="stat-icon info"><i class="mdi mdi-clock-fast"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-semibold text-uppercase">Failed Jobs</span>
                                <div class="h4 fw-bold mb-0 mt-1 {{ ($healthMetrics['failed_jobs'] ?? 0) > 0 ? 'text-danger' : '' }}">
                                    {{ number_format($healthMetrics['failed_jobs']) }}
                                </div>
                            </div>
                            <div class="stat-icon danger"><i class="mdi mdi-alert-octagon-outline"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-semibold text-uppercase">Free Storage</span>
                                <div class="h4 fw-bold mb-0 mt-1">{{ $healthMetrics['disk_free_gb'] }} GB</div>
                            </div>
                            <div class="stat-icon success"><i class="mdi mdi-harddisk"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-semibold text-uppercase">Runtime</span>
                                <div class="h6 fw-bold mb-0 mt-2">PHP {{ $healthMetrics['php_version'] }}</div>
                                <small class="text-muted">Laravel {{ $healthMetrics['laravel_version'] }}</small>
                            </div>
                            <div class="stat-icon primary"><i class="mdi mdi-lan-connect"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(($healthMetrics['failed_jobs'] ?? 0) > 0)
            <div class="alert alert-warning d-flex align-items-center gap-2" role="alert">
                <i class="mdi mdi-alert-outline mdi-20px"></i>
                <div>{{ number_format($healthMetrics['failed_jobs']) }} failed job(s) need attention — run <code>php artisan queue:retry all</code> after investigating.</div>
            </div>
        @endif
    </div>
    @include('Admin.templates.footer')
</div>
