<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Health & Monitor</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800 p-8">
    <div class="max-w-5xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">System Health & Queue Monitoring</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="p-6 bg-white rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase text-slate-400">Database Connection</span>
                    <div class="text-xl font-bold mt-1 text-slate-800">MySQL Database</div>
                </div>
                @if($healthMetrics['database'])
                    <span class="px-3 py-1 bg-emerald-100 text-emerald-800 rounded-full font-semibold text-xs">Healthy</span>
                @else
                    <span class="px-3 py-1 bg-rose-100 text-rose-800 rounded-full font-semibold text-xs">Unhealthy</span>
                @endif
            </div>

            <div class="p-6 bg-white rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase text-slate-400">Cache Driver</span>
                    <div class="text-xl font-bold mt-1 text-slate-800">Application Cache</div>
                </div>
                @if($healthMetrics['cache'])
                    <span class="px-3 py-1 bg-emerald-100 text-emerald-800 rounded-full font-semibold text-xs">Healthy</span>
                @else
                    <span class="px-3 py-1 bg-rose-100 text-rose-800 rounded-full font-semibold text-xs">Unhealthy</span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="p-6 bg-white rounded-2xl shadow-sm border border-slate-100">
                <span class="text-xs font-semibold uppercase text-slate-400">Pending Jobs</span>
                <div class="text-2xl font-bold text-indigo-600 mt-1">{{ $healthMetrics['pending_jobs'] }}</div>
            </div>
            <div class="p-6 bg-white rounded-2xl shadow-sm border border-slate-100">
                <span class="text-xs font-semibold uppercase text-slate-400">Failed Jobs</span>
                <div class="text-2xl font-bold text-rose-600 mt-1">{{ $healthMetrics['failed_jobs'] }}</div>
            </div>
            <div class="p-6 bg-white rounded-2xl shadow-sm border border-slate-100">
                <span class="text-xs font-semibold uppercase text-slate-400">Free Storage</span>
                <div class="text-2xl font-bold text-slate-800 mt-1">{{ $healthMetrics['disk_free_gb'] }} GB</div>
            </div>
            <div class="p-6 bg-white rounded-2xl shadow-sm border border-slate-100">
                <span class="text-xs font-semibold uppercase text-slate-400">PHP Version</span>
                <div class="text-2xl font-bold text-slate-800 mt-1">{{ $healthMetrics['php_version'] }}</div>
            </div>
        </div>
    </div>
</body>
</html>
