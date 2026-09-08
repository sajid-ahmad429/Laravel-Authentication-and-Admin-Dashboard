<!-- Layout container -->
<div class="layout-page">
    <!-- Navbar -->
    <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
        id="layout-navbar">
        <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
            <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)" id="layout-menu-toggle">
                <i class="mdi mdi-menu mdi-24px"></i>
            </a>
        </div>

        <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
            <ul class="navbar-nav flex-row align-items-center ms-auto">
                <!-- Theme (light/dark/system) switcher -->
                <li class="nav-item dropdown-style-switcher dropdown me-2 me-xl-0">
                    <a class="nav-link btn btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow"
                        href="javascript:void(0);" data-bs-toggle="dropdown" aria-label="Change theme">
                        <i class="mdi mdi mdi-theme-light-dark mdi-24px"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-styles">
                        <li>
                            <a class="dropdown-item" href="javascript:void(0);" data-theme="light">
                                <span class="align-middle"><i class="mdi mdi-weather-sunny me-2"></i>Light</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="javascript:void(0);" data-theme="dark">
                                <span class="align-middle"><i class="mdi mdi-weather-night me-2"></i>Dark</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="javascript:void(0);" data-theme="system">
                                <span class="align-middle"><i class="mdi mdi-monitor me-2"></i>System</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <!--/ Style Switcher -->

                <!-- User dropdown -->
                <li class="nav-item navbar-dropdown dropdown-user dropdown">
                    <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                        <div class="avatar avatar-online">
                            @php
                                $navUser = \App\Models\User::find(session('id'));
                                $navInitial = strtoupper(substr((string) session('name', 'U'), 0, 1));
                            @endphp
                            @if($navUser && $navUser->avatar)
                                <img src="{{ asset($navUser->avatar) }}" alt="" class="w-px-40 h-auto rounded-circle" />
                            @else
                                <span class="avatar-initial rounded-circle bg-label-primary w-px-40 h-auto">{{ $navInitial }}</span>
                            @endif
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="{{ route('panel.profile.index') }}">
                                <div class="d-flex">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="avatar avatar-online">
                                            @if($navUser && $navUser->avatar)
                                                <img src="{{ asset($navUser->avatar) }}" alt="" class="w-px-40 h-auto rounded-circle" />
                                            @else
                                                <span class="avatar-initial rounded-circle bg-label-primary w-px-40 h-auto">{{ $navInitial }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <span class="fw-medium d-block">{{ ucwords(session('name', 'User')) }}</span>
                                        <small class="text-muted">{{ ucwords(session('role', 'User')) }}</small>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li><div class="dropdown-divider"></div></li>
                        <li>
                            <a class="dropdown-item" href="{{ route('panel.profile.index') }}">
                                <i class="mdi mdi-account-outline me-2"></i>
                                <span class="align-middle">My Profile</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('panel.plans.index') }}">
                                <i class="mdi mdi-credit-card-outline me-2"></i>
                                <span class="align-middle">Billing &amp; Plans</span>
                            </a>
                        </li>
                        <li><div class="dropdown-divider"></div></li>
                        <li>
                            <!-- POST logout (CSRF protected) -->
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="mdi mdi-logout me-2"></i>
                                    <span class="align-middle">Log Out</span>
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
                <!--/ User -->
            </ul>
        </div>

        <!-- Search Small Screens -->
        <div class="navbar-search-wrapper search-input-wrapper d-none">
            <input type="text" class="form-control search-input container-xxl border-0" placeholder="Search..."
                aria-label="Search..." />
            <i class="mdi mdi-close search-toggler cursor-pointer"></i>
        </div>
    </nav>
    <!-- / Navbar -->
