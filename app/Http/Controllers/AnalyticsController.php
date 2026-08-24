<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(): View
    {
        $metrics = Cache::remember('analytics_summary_metrics', 300, function () {
            return [
                'total_users'     => User::count(),
                'active_users'    => User::where('status', 1)->count(),
                'total_activities'=> ActivityLog::count(),
                'new_this_month'  => User::where('created_at', '>=', now()->startOfMonth())->count(),
            ];
        });

        $activeMenu = 'analytics';
        return view('admin.analytics.index', compact('metrics', 'activeMenu'));
    }
}
