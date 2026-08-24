<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        $plans = [
            [
                'name' => 'Free Tier',
                'price' => '$0 / mo',
                'features' => ['Up to 5 Users', 'Basic Analytics', 'Community Support'],
                'color' => 'bg-slate-50 border-slate-200'
            ],
            [
                'name' => 'Pro Plan',
                'price' => '$29 / mo',
                'features' => ['Up to 50 Users', 'Advanced Analytics', 'Priority Queue Email', 'Audit Logs'],
                'color' => 'bg-indigo-50 border-indigo-200'
            ],
            [
                'name' => 'Enterprise SaaS',
                'price' => '$99 / mo',
                'features' => ['Unlimited Users', 'Spatie Roles & Permissions', 'Dedicated Support', 'SLA 99.9%'],
                'color' => 'bg-purple-50 border-purple-200'
            ],
        ];

        $activeMenu = 'plans';
        return view('admin.plans.index', compact('plans', 'activeMenu'));
    }
}
