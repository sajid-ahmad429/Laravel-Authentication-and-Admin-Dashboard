<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SaaS Analytics Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800 p-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">SaaS Platform Analytics & Metrics</h1>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="p-6 bg-white rounded-2xl shadow-sm border border-slate-100">
                <span class="text-xs font-semibold uppercase text-slate-400">Total Users</span>
                <div class="text-3xl font-bold text-slate-800 mt-2">{{ $metrics['total_users'] }}</div>
            </div>
            <div class="p-6 bg-white rounded-2xl shadow-sm border border-slate-100">
                <span class="text-xs font-semibold uppercase text-slate-400">Active Accounts</span>
                <div class="text-3xl font-bold text-emerald-600 mt-2">{{ $metrics['active_users'] }}</div>
            </div>
            <div class="p-6 bg-white rounded-2xl shadow-sm border border-slate-100">
                <span class="text-xs font-semibold uppercase text-slate-400">Activity Logs</span>
                <div class="text-3xl font-bold text-indigo-600 mt-2">{{ $metrics['total_activities'] }}</div>
            </div>
            <div class="p-6 bg-white rounded-2xl shadow-sm border border-slate-100">
                <span class="text-xs font-semibold uppercase text-slate-400">New This Month</span>
                <div class="text-3xl font-bold text-purple-600 mt-2">{{ $metrics['new_this_month'] }}</div>
            </div>
        </div>
    </div>
</body>
</html>
