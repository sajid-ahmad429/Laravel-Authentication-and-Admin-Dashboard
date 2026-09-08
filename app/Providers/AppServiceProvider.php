<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Named rate limiters used by the auth routes.
     *
     * Keys combine the client IP with the attempted identity (email) so an
     * attacker cannot rotate IPs trivially and one victim's email cannot be
     * lock-out-spammed from a single machine.
     */
    protected function configureRateLimiting(): void
    {
        $limits = (array) config('auth.throttle', []);

        RateLimiter::for('login', function (Request $request) use ($limits) {
            $cfg = $limits['login'] ?? ['max_attempts' => 5, 'decay_seconds' => 60];

            return [
                Limit::perMinute((int) $cfg['max_attempts'])->by($this->throttleKey($request, 'login')),
                Limit::perMinute(60)->by('ip|'.$request->ip()),
            ];
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinutes(10, 5)->by($this->throttleKey($request, 'register'));
        });

        RateLimiter::for('reset', function (Request $request) {
            return Limit::perMinutes(5, 3)->by($this->throttleKey($request, 'reset'));
        });
    }

    protected function throttleKey(Request $request, string $scope): string
    {
        $identity = strtolower((string) $request->input('email', $request->ip()));

        return $scope.'|'.sha1($identity.'|'.$request->ip());
    }
}
