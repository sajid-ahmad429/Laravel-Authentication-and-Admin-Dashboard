@include('Admin.templates.header')

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">SaaS Platform Analytics & Metrics</h4>
                <p class="text-muted mb-0">Overview of user growth, active accounts, and audit log activities.</p>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted fw-semibold mb-1">Total Users</p>
                                <h3 class="mb-0 fw-bold text-dark">{{ $metrics['total_users'] }}</h3>
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

            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted fw-semibold mb-1">Active Accounts</p>
                                <h3 class="mb-0 fw-bold text-success">{{ $metrics['active_users'] }}</h3>
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

            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted fw-semibold mb-1">Activity Logs</p>
                                <h3 class="mb-0 fw-bold text-info">{{ $metrics['total_activities'] }}</h3>
                            </div>
                            <div class="avatar avatar-lg">
                                <div class="avatar-initial bg-label-info rounded-circle p-3">
                                    <i class="mdi mdi-history mdi-24px"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted fw-semibold mb-1">New This Month</p>
                                <h3 class="mb-0 fw-bold text-warning">{{ $metrics['new_this_month'] }}</h3>
                            </div>
                            <div class="avatar avatar-lg">
                                <div class="avatar-initial bg-label-warning rounded-circle p-3">
                                    <i class="mdi mdi-account-plus-outline mdi-24px"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    @include('Admin.templates.footer')
</div>
