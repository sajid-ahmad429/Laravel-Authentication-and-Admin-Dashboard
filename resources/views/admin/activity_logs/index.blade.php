<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Master Logs</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800 p-8">
    <div class="max-w-6xl mx-auto bg-white p-6 rounded-xl shadow-md">
        <h1 class="text-2xl font-bold mb-6">Activity Master Audit Trail</h1>

        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b bg-slate-50 text-slate-600 text-sm">
                    <th class="p-3">ID</th>
                    <th class="p-3">User</th>
                    <th class="p-3">Action</th>
                    <th class="p-3">Table</th>
                    <th class="p-3">Log Message</th>
                    <th class="p-3">IP Address</th>
                    <th class="p-3">Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b text-sm">
                    <td class="p-3 text-slate-400" colspan="7">Audit logs loaded via DataTables server-side endpoint.</td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>
