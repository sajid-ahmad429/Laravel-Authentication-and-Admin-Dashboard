@section('auth_title', 'Forgot password')
@include('Admin.templates.auth-header')


<div class="authentication-wrapper authentication-cover">
    <a href="{{ route('login') }}" class="auth-cover-brand d-flex align-items-center gap-2">
        <span class="app-brand-logo demo">
            <span style="color: var(--bs-primary)">
                <svg width="26" height="20" viewBox="0 0 38 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M30.0944 2.22569C29.0511 0.444187 26.7508 -0.172113 24.9566 0.849138C23.1623 1.87039 22.5536 4.14247 23.5969 5.92397L30.5368 17.7743C31.5801 19.5558 33.8804 20.1721 35.6746 19.1509C37.4689 18.1296 38.0776 15.8575 37.0343 14.076L30.0944 2.22569Z" fill="currentColor"/>
                    <path d="M30.171 2.22569C29.1277 0.444187 26.8274 -0.172113 25.0332 0.849138C23.2389 1.87039 22.6302 4.14247 23.6735 5.92397L30.6134 17.7743C31.6567 19.5558 33.957 20.1721 35.7512 19.1509C37.5455 18.1296 38.1542 15.8575 37.1109 14.076L30.171 2.22569Z" fill="currentColor" fill-opacity="0.4"/>
                    <path d="M22.9676 2.22569C24.0109 0.444187 26.3112 -0.172113 28.1054 0.849138C29.8996 1.87039 30.5084 4.14247 29.4651 5.92397L22.5251 17.7743C21.4818 19.5558 19.1816 20.1721 17.3873 19.1509C15.5931 18.1296 14.9843 15.8575 16.0276 14.076L22.9676 2.22569Z" fill="currentColor"/>
                    <path d="M14.9558 2.22569C13.9125 0.444187 11.6122 -0.172113 9.818 0.849138C8.02377 1.87039 7.41502 4.14247 8.45833 5.92397L15.3983 17.7743C16.4416 19.5558 18.7418 20.1721 20.5361 19.1509C22.3303 18.1296 22.9391 15.8575 21.8958 14.076L14.9558 2.22569Z" fill="currentColor" fill-opacity="0.4"/>
                    <path d="M14.9558 2.22569C13.9125 0.444187 11.6122 -0.172113 9.818 0.849138C8.02377 1.87039 7.41502 4.14247 8.45833 5.92397L15.3983 17.7743C16.4416 19.5558 18.7418 20.1721 20.5361 19.1509C22.3303 18.1296 22.9391 15.8575 21.8958 14.076L14.9558 2.22569Z" fill="currentColor"/>
                    <path d="M7.82901 2.22569C8.87231 0.444187 11.1726 -0.172113 12.9668 0.849138C14.7611 1.87039 15.3265 5.92397 14.3265 5.92397L7.38656 17.7743C6.34325 19.5558 4.04298 20.1721 2.24875 19.1509C0.454514 18.1296 -0.154233 15.8575 0.88907 14.076L7.82901 2.22569Z" fill="currentColor"/>
                </svg>
            </span>
        </span>
        <span class="app-brand-text demo text-heading fw-bold">{{ config('app.name', 'AdminPro') }}</span>
    </a>

    <div class="authentication-inner row m-0">
        <!-- /Left Text -->
        <div class="d-none d-lg-flex col-lg-7 col-xl-8 align-items-center p-5">
            <div class="w-100 h-100 position-relative">
                <img src="{{ asset('assets/img/illustrations/auth-forgot-password-illustration-light.png') }}"
                    class="authentication-image position-absolute w-100" alt="auth-illustration"
                    data-app-light-img="illustrations/auth-forgot-password-illustration-light.png"
                    data-app-dark-img="illustrations/auth-forgot-password-illustration-dark.png" />
                <img src="{{ asset('assets/img/illustrations/auth-cover-forgot-password-mask-light.png') }}"
                    class="authentication-image position-absolute w-100" alt="mask"
                    data-app-light-img="illustrations/auth-cover-forgot-password-mask-light.png"
                    data-app-dark-img="illustrations/auth-cover-forgot-password-mask-dark.png" />
            </div>
        </div>
        <!-- /Left Text -->

        <!-- Forgot Password -->
        <div class="d-flex col-12 col-lg-5 col-xl-4 align-items-center authentication-bg position-relative py-sm-5 px-4 py-4">
            <div class="w-px-400 mx-auto pt-5 pt-lg-0">
                <h3 class="mb-1 fw-bold">Forgot Password? 🔒</h3>
                <p class="mb-4">Enter your email and we'll send you reset instructions.</p>

                @if (session('success'))
                    <div class="alert alert-success" role="alert">{{ session('success') }}</div>
                @endif

                @if (session('danger'))
                    <div class="alert alert-danger" role="alert">{{ session('danger') }}</div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form id="formAuthentication" class="mb-3" action="{{ route('forgotpassword.attempt') }}" method="POST">
                    @csrf
                    <div class="form-floating form-floating-outline mb-4">
                        <input type="email" class="form-control" id="email" name="email"
                            placeholder="Enter your email" value="{{ old('email') }}" autofocus required />
                        <label for="email">Email</label>
                    </div>

                    <button type="submit" class="btn btn-primary d-grid w-100 mb-4">Send Reset Link</button>

                    <a class="d-flex align-items-center justify-content-center" href="{{ route('login') }}">
                        <i class="mdi mdi-chevron-left me-1"></i>Back to login
                    </a>
                </form>
            </div>
        </div>
        <!-- /Forgot Password -->
    </div>
</div>

@include('Admin.templates.auth-footer')
