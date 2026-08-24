@include('Admin.templates.header')

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">SaaS Subscription Plans</h4>
                <p class="text-muted mb-0">Select and manage platform tier subscriptions.</p>
            </div>
        </div>

        <div class="row g-4">
            @foreach($plans as $plan)
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 text-center">
                        <h5 class="fw-bold mb-2">{{ $plan['name'] }}</h5>
                        <h2 class="text-primary fw-extrabold mb-4">{{ $plan['price'] }}</h2>
                        <ul class="list-unstyled text-start mb-4">
                            @foreach($plan['features'] as $feature)
                            <li class="mb-2 d-flex align-items-center gap-2">
                                <i class="mdi mdi-check-circle text-success fs-5"></i>
                                <span class="text-muted small">{{ $feature }}</span>
                            </li>
                            @endforeach
                        </ul>
                        <button class="btn btn-primary w-100 shadow-sm">Select Plan</button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

    </div>
    @include('Admin.templates.footer')
</div>
