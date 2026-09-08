<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index()
    {
        if (! session()->has('isLoggedIn') && ! auth()->check()) {
            return redirect()->route('login');
        }

        $data['activeMenu'] = 'users';
        $data['assetsJs'] = ['app-user-list'];

        $data['active'] = Cache::remember('count_active', 120, function () {
            return DB::table('users')->where('status', 1)->where('trash', 0)->count();
        });

        $data['inactive'] = Cache::remember('count_inactive', 120, function () {
            return DB::table('users')->where('status', 0)->where('trash', 0)->count();
        });

        $data['totalUsers'] = Cache::remember('count_total', 120, function () {
            return DB::table('users')->where('trash', 0)->count();
        });

        return view('masters.users.list', $data);
    }

    public function store(Request $request): JsonResponse
    {
        $userId = $request->input('user_id');
        $isUpdating = $request->filled('user_id') && (int) $userId !== 0;

        // Normalize the role before validation so "Admin" matches the "admin" record.
        if ($request->filled('user-role')) {
            $request->merge(['user-role' => strtolower(trim((string) $request->input('user-role')))]);
        }

        $rules = [
            'userFullname' => ['required', 'string', 'max:255', 'regex:/^[\pL\s\-\.\']+$/u'],
            'userEmail' => ['required', 'email', 'max:255', $isUpdating ? 'unique:users,email,'.$userId : 'unique:users,email'],
            'userContact' => ['required', 'string', 'max:15', $isUpdating ? 'unique:users,contact_no,'.$userId : 'unique:users,contact_no'],
            'companyName' => ['nullable', 'string', 'max:150'],
            'country' => ['nullable', 'string', 'max:100'],
            'user-role' => ['nullable', 'string', 'max:50', 'exists:roles,name'],
            'user-plan' => ['nullable', 'string', 'max:50'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => 'Validation error occurred.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $roleInput = $request->input('user-role');
        $actorRole = strtolower((string) session('role', ''));

        // Only a superadmin may grant the superadmin role.
        if (! empty($roleInput) && $roleInput === 'superadmin' && $actorRole !== 'superadmin') {
            return response()->json([
                'status' => 0,
                'message' => 'Only a superadmin can assign the superadmin role.',
            ], 403);
        }

        DB::beginTransaction();
        try {
            $data = [
                'name' => $request->input('userFullname'),
                'email' => $request->input('userEmail'),
                'contact_no' => $request->input('userContact'),
                'company_name' => $request->input('companyName'),
                'country' => $request->input('country'),
                'plan' => $request->input('user-plan'),
            ];

            if ($isUpdating) {
                $user = User::find($userId);

                if (! $user) {
                    DB::rollBack();

                    return response()->json(['status' => 0, 'message' => 'Target user not found.'], 404);
                }

                // Only a superadmin may modify another superadmin account.
                if ($this->isSuperAdmin($user) && $actorRole !== 'superadmin') {
                    DB::rollBack();

                    return response()->json([
                        'status' => 0,
                        'message' => 'Only a superadmin can modify a superadmin account.',
                    ], 403);
                }

                if (! empty($roleInput)) {
                    $data['roles'] = $roleInput;
                }

                $user->update($data);

                if (! empty($roleInput)) {
                    $user->syncRoles([$roleInput]);
                }

                DB::commit();
                $this->clearUserCache((int) $user->id);

                return response()->json(['status' => 1, 'message' => 'Record Details Updated Successfully']);
            }

            // New users created by an admin are pre-vetted: activate immediately
            // with a random one-time password (returned once so the admin can
            // share it; the user should change it after first login).
            // NOTE: plaintext assignment — the model's `hashed` cast hashes it.
            $tempPassword = Str::random(12);
            $data['password'] = $tempPassword;
            $data['activated'] = 1;
            $data['status'] = 1;
            $data['trash'] = 0;

            if (! empty($roleInput)) {
                $data['roles'] = $roleInput;
            }

            $newUser = User::create($data);

            if (! empty($roleInput)) {
                $newUser->syncRoles([$roleInput]);
            }

            DB::commit();
            $this->clearUserCache();

            return response()->json([
                'status' => 1,
                'message' => 'Record Details Added Successfully',
                'temp_password' => $tempPassword,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('User store/update failed: '.$e->getMessage(), ['user_id' => $userId]);

            return response()->json(['status' => 0, 'message' => 'Could not save the user. Please try again.'], 500);
        }
    }

    public function getTableData(Request $request): JsonResponse
    {
        if (! $request->ajax()) {
            return response()->json(['status' => 0, 'message' => 'Invalid Request'], 400);
        }

        $validated = $request->validate([
            'draw' => ['required', 'integer'],
            'start' => ['required', 'integer', 'min:0'],
            'length' => ['required', 'integer', 'min:1', 'max:100'],
            'search.value' => ['nullable', 'string', 'max:100'],
            'order' => ['nullable', 'array'],
            'order.*.column' => ['required', 'integer'],
            'order.*.dir' => ['required', 'in:asc,desc'],
            'trash_filter' => ['nullable', 'in:0,1'],
            'status_filter' => ['nullable', 'in:0,1,2'],
        ]);

        $columnMap = [
            0 => 'id',
            1 => 'name',
            2 => 'email',
            3 => 'roles',
            4 => 'plan',
            5 => 'country',
            6 => 'status',
        ];

        $query = User::query()->select([
            'id',
            'name',
            'email',
            'roles',
            'plan',
            'country',
            'status',
            'trash',
            'contact_no',
            'company_name',
        ]);

        $trashFilter = (int) ($validated['trash_filter'] ?? 0);
        $query->where('trash', $trashFilter);

        if (isset($validated['status_filter']) && $validated['status_filter'] !== '') {
            $query->where('status', $validated['status_filter']);
        }

        // Intentionally uncached: the indexed COUNT(*) is cheap, and caching
        // per filter-combination previously served stale totals (the cache
        // keys were never invalidated on write).
        $recordsTotal = (clone $query)->count();

        $searchValue = $validated['search']['value'] ?? null;
        if (! empty($searchValue)) {
            $query->where(function ($sub) use ($searchValue) {
                $sub->where('name', 'LIKE', "{$searchValue}%")
                    ->orWhere('email', 'LIKE', "{$searchValue}%")
                    ->orWhere('contact_no', 'LIKE', "{$searchValue}%")
                    ->orWhere('company_name', 'LIKE', "{$searchValue}%");
            });
        }

        $recordsFiltered = (clone $query)->count();

        $aggregateData = DB::table('users')
            ->selectRaw('
            COUNT(CASE WHEN status = 1 AND trash = 0 THEN 1 END) as active_count,
            COUNT(CASE WHEN status = 0 AND trash = 0 THEN 1 END) as inactive_count,
            COUNT(CASE WHEN trash = 1 THEN 1 END) as trashed_count
        ')->first();

        $sortColumnIndex = $validated['order'][0]['column'] ?? 0;
        $sortDirection = $validated['order'][0]['dir'] ?? 'desc';
        $sortColumn = $columnMap[$sortColumnIndex] ?? 'id';
        $query->orderBy($sortColumn, $sortDirection);

        $users = $query->skip($validated['start'])->take($validated['length'])->get();

        $data = [];
        foreach ($users as $user) {
            $encodedId = base64_encode((string) $user->id);

            if ((int) $user->trash === 1) {
                $actionButtons = '<button class="btn btn-sm btn-success btn-restore" data-id="'.$encodedId.'"> <i class="mdi mdi-restore me-1"></i> Restore </button>';
            } else {
                $actionButtons = '
                <div class="dropdown">
                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                        <i class="mdi mdi-dots-vertical"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item edit-user-btn" href="javascript:void(0);" data-id="'.$encodedId.'"><i class="mdi mdi-pencil-outline me-1"></i> Edit</a>
                        <a class="dropdown-item btn-trash text-danger" href="javascript:void(0);" data-id="'.$encodedId.'"><i class="mdi mdi-trash-can-outline me-1"></i> Trash</a>
                    </div>
                </div>';
            }

            $statusBadge = (int) $user->status === 1
                ? '<span class="badge bg-label-success">ACTIVE</span>'
                : '<span class="badge bg-label-secondary">INACTIVE</span>';

            $data[] = [
                'id' => $user->id,
                'full_name' => ucwords(e($user->name)),
                'email' => e($user->email),
                'role' => '<span class="text-warning"><i class="mdi mdi-cog-outline me-1"></i>'.(ucwords(e((string) $user->roles)) ?: '-').'</span>',
                'current_plan' => ucwords(e((string) $user->plan)) ?: '-',
                'country' => e((string) $user->country) ?: '-',
                'status' => $statusBadge,
                'actions' => '<div class="text-center">'.$actionButtons.'</div>',
            ];
        }

        return response()->json([
            'draw' => (int) $validated['draw'],
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'totalActiveRecods' => $aggregateData->active_count ?? 0,
            'totalInActiveRecods' => $aggregateData->inactive_count ?? 0,
            'totalTrashedRecods' => $aggregateData->trashed_count ?? 0,
            'data' => $data,
        ]);
    }

    public function getUserDetails(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'string', 'max:32'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => 'Parameters missing.'], 422);
        }

        $id = base64_decode($request->input('id'), true);

        if ($id === false || ! ctype_digit((string) $id)) {
            return response()->json(['status' => 0, 'message' => 'Invalid user reference.'], 422);
        }

        $usersData = Cache::remember("user_details_{$id}", 300, function () use ($id) {
            return User::where('id', $id)->first();
        });

        if (! $usersData) {
            return response()->json(['status' => 0, 'message' => 'Requested record not found.'], 404);
        }

        // toArray() respects $hidden, so password / tokens are never exposed.
        return response()->json(array_merge($usersData->toArray(), ['status' => 1]));
    }

    public function toggleTrash(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'string', 'max:32'],
            'action_type' => ['required', 'in:0,1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => 'Validation error occurred.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $id = base64_decode($request->input('id'), true);

        if ($id === false || ! ctype_digit((string) $id)) {
            return response()->json(['status' => 0, 'message' => 'Invalid user reference.'], 422);
        }

        $user = User::find($id);

        if (! $user) {
            return response()->json(['status' => 0, 'message' => 'Target user not found.'], 404);
        }

        // Nobody can trash their own account.
        if ((int) $user->id === (int) session('id', 0)) {
            return response()->json(['status' => 0, 'message' => 'You cannot move your own account to trash.'], 403);
        }

        // Only a superadmin may trash/restore a superadmin account.
        if ($this->isSuperAdmin($user) && strtolower((string) session('role', '')) !== 'superadmin') {
            return response()->json(['status' => 0, 'message' => 'Only a superadmin can modify a superadmin account.'], 403);
        }

        $targetAction = (int) $request->input('action_type');

        DB::beginTransaction();
        try {
            $previousData = ['trash' => $user->trash];

            $user->update(['trash' => $targetAction]);

            if (function_exists('track_activity')) {
                track_activity($previousData, $user, ['trash' => $targetAction], $user->id, 'users', 1);
            }

            DB::commit();

            $this->clearUserCache($user->id);

            return response()->json([
                'status' => 1,
                'message' => $targetAction === 1
                    ? 'Record moved to trash successfully.'
                    : 'Record restored successfully.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Toggle trash failed: '.$e->getMessage(), ['user_id' => $user->id]);

            return response()->json([
                'status' => 0,
                'message' => 'Could not update the record. Please try again.',
            ], 500);
        }
    }

    /**
     * Check whether the given user holds the superadmin role (Spatie or legacy column).
     */
    protected function isSuperAdmin(User $user): bool
    {
        try {
            if ($user->hasRole('superadmin')) {
                return true;
            }
        } catch (\Throwable $e) {
            // Fall through to the legacy column check.
        }

        return strtolower((string) $user->getAttribute('roles')) === 'superadmin';
    }

    protected function clearUserCache(?int $userId = null): void
    {
        Cache::forget('count_active');
        Cache::forget('count_inactive');
        Cache::forget('count_total');
        Cache::forget('users_all_count');
        Cache::forget('users_inactive_count');
        Cache::forget('users_active_count');
        Cache::forget('users_list_data');
        Cache::forget('analytics_summary_metrics');

        if ($userId) {
            Cache::forget("user_details_{$userId}");
        }
    }
}
