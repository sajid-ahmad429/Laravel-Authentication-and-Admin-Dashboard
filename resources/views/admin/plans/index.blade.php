<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SaaS Subscription Plans</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800 p-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold mb-8 text-center">SaaS Subscription Plans</h1>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($plans as $plan)
            <div class="p-6 rounded-2xl border shadow-sm bg-white hover:shadow-lg transition">
                <h2 class="text-xl font-bold mb-2">{{ $plan['name'] }}</h2>
                <div class="text-3xl font-extrabold text-indigo-600 mb-6">{{ $plan['price'] }}</div>
                <ul class="space-y-3 mb-8 text-sm text-slate-600">
                    @foreach($plan['features'] as $feature)
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>{{ $feature }}</span>
                    </li>
                    @endforeach
                </ul>
                <button class="w-full py-2.5 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition">Select Plan</button>
            </div>
            @endforeach
        </div>
    </div>
</body>
</html>
