<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    protected User $users;

    public function __construct(User $users)
    {
        $this->users = $users;
        date_default_timezone_set('Asia/Kolkata');
    }

    public function index()
    {
        if (!session()->has('isLoggedIn')) {
            return redirect()->to('sysCtrlLogin');
        }

        $data['activeMenu'] = "users";
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
        $isUpdating = $request->has('user_id') && !empty($userId) && $userId != 0;

        $rules = [
            'userFullname' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s\-]+$/'],
            'userEmail'    => ['required', 'email', $isUpdating ? 'unique:users,email,' . $userId : 'unique:users,email'],
            'userContact'  => ['required', 'string', 'max:10', $isUpdating ? 'unique:users,contact_no,' . $userId : 'unique:users,contact_no'],
            'companyName'  => ['nullable', 'string', 'max:150'],
            'country'      => ['nullable', 'string', 'max:100'],
            'user-role'    => ['nullable', 'string', 'max:50'],
            'user-plan'    => ['nullable', 'string', 'max:50'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 0,
                'message' => 'Validation error occurred.',
                'errors'  => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $data = [
                'name'         => $request->input('userFullname'),
                'email'        => $request->input('userEmail'),
                'contact_no'   => $request->input('userContact'),
                'company_name' => $request->input('companyName'),
                'country'      => $request->input('country'),
                'roles'        => $request->input('user-role'),
                'plan'         => $request->input('user-plan'),
            ];

            if ($isUpdating) {
                $user = User::find($userId);
                if (!$user) {
                    DB::rollBack();
                    return response()->json(['status' => 0, 'message' => 'Target user footprint not found.'], 442);
                }

                $user->update($data);

                DB::commit();
                $this->clearUserCache($userId);

                return response()->json(['status' => 1, 'message' => 'Record Details Updated Successfully']);
            } else {
                $data['password'] = bcrypt('Smart@#123');
                User::create($data);

                DB::commit();
                $this->clearUserCache();

                return response()->json(['status' => 1, 'message' => 'Record Details Added Successfully']);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error tracking inside creation/update pipeline: ' . $e->getMessage());
            return response()->json(['status' => 0, 'message' => 'Critical database layer transaction exception.'], 500);
        }
    }

    public function getTableData(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return response()->json(['status' => 0, 'message' => 'Invalid Request'], 400);
        }

        $validated = $request->validate([
            'draw'           => ['required', 'integer'],
            'start'          => ['required', 'integer', 'min:0'],
            'length'         => ['required', 'integer', 'min:1', 'max:100'],
            'search.value'   => ['nullable', 'string', 'max:100'],
            'order'          => ['nullable', 'array'],
            'order.*.column' => ['required', 'integer'],
            'order.*.dir'    => ['required', 'in:asc,desc'],
        ]);

        $columnMap = [
            0 => 'id',
            1 => 'name',
            2 => 'email',
            3 => 'roles',
            4 => 'plan',
            5 => 'country',
            6 => 'status'
        ];

        // Base query setup (Searchable columns select me daal diye taaki crash na ho)
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
            'company_name'
        ]);

        // 1. Pehle tab/trash filter apply karein
        $trashFilter = $request->has('trash_filter') ? intval($request->input('trash_filter')) : 0;
        $query->where('trash', $trashFilter);

        // 2. Custom status filter apply karein
        if ($request->has('status_filter') && $request->input('status_filter') !== '') {
            $query->where('status', $request->input('status_filter'));
        }

        // ⭐ STEP 1: Yeh aapka base screen total hai (Bina search keyword ke)
        // Agar Trash filter active hai toh Trash ke saare records ka total batayega (e.g. 18)
        $recordsTotal = $query->count();

        // 3. Ab global search keyword filter apply karein
        if (!empty($validated['search']['value'])) {
            $search = $validated['search']['value'];
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'LIKE', "{$search}%")
                    ->orWhere('email', 'LIKE', "{$search}%")
                    ->orWhere('contact_no', 'LIKE', "{$search}%")
                    ->orWhere('company_name', 'LIKE', "{$search}%");
            });
        }

        // ⭐ STEP 2: Yeh search filter lagne ke baad ka total hai (e.g. 15)
        $recordsFiltered = $query->count();

        // Dashboard counters calculation
        $aggregateData = DB::table('users')
            ->selectRaw("
            COUNT(CASE WHEN status = 1 AND trash = 0 THEN 1 END) as active_count,
            COUNT(CASE WHEN status = 0 AND trash = 0 THEN 1 END) as inactive_count,
            COUNT(CASE WHEN trash = 1 THEN 1 END) as trashed_count
        ")->first();

        // Sorting Logic
        $sortColumnIndex = isset($validated['order'][0]['column']) ? $validated['order'][0]['column'] : 0;
        $sortDirection = isset($validated['order'][0]['dir']) ? $validated['order'][0]['dir'] : 'desc';
        $sortColumn = $columnMap[$sortColumnIndex] ?? 'id';
        $query->orderBy($sortColumn, $sortDirection);

        // Pagination Limit Apply
        $users = $query->skip($validated['start'])->take($validated['length'])->get();

        $data = [];
        foreach ($users as $user) {
            $encodedId = base64_encode($user->id);

            if ($user->trash == 1) {
                $actionButtons = '<button class="btn-restore px-3 py-1.5 text-xs font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition" data-id="' . $encodedId . '"> Restore Log </button>';
            } else {
                $actionButtons = '<div class="inline-flex rounded-md shadow-sm">
                <button class="edit-user-btn px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-l-lg hover:bg-indigo-700 transition" data-id="' . $encodedId . '"> Edit </button>
                <button class="btn-trash px-3 py-1.5 text-xs font-medium text-white bg-rose-600 rounded-r-lg hover:bg-rose-700 transition" data-id="' . $encodedId . '"> Trash </button>
            </div>';
            }

            $data[] = [
                'id'           => $user->id,
                'full_name'    => ucwords(e($user->name)),
                'email'        => e($user->email),
                'role'         => ucwords(e($user->roles)) ?: '-',
                'current_plan' => ucwords(e($user->plan)) ?: '-',
                'country'      => e($user->country) ?: '-',
                'status'       => $user->status,
                'actions'      => '<div class="text-center">' . $actionButtons . '</div>'
            ];
        }

        return response()->json([
            'draw'                => intval($validated['draw']),
            'recordsTotal'        => $recordsTotal,        // Dynamic Total Count 
            'recordsFiltered'     => $recordsFiltered,     // Filtered Count
            'totalActiveRecods'   => $aggregateData->active_count ?? 0,
            'totalInActiveRecods' => $aggregateData->inactive_count ?? 0,
            'totalTrashedRecods'  => $aggregateData->trashed_count ?? 0,
            'data'                => $data
        ]);
    }


    public function getUserDetails(Request $request): JsonResponse
    {
        if ($request->has('id') && !empty($request->input('id'))) {
            try {
                $id = base64_decode($request->input('id'), true);
                if (!$id) {
                    throw new \InvalidArgumentException("Invalid payload encryption signature.");
                }

                $cacheKey = "user_details_{$id}";
                $usersData = Cache::remember($cacheKey, 300, function () use ($id) {
                    return User::where('id', $id)->first();
                });

                if ($usersData) {
                    $responseData = $usersData->toArray();
                    unset($responseData['password'], $responseData['remember_token']);
                    $responseData['status'] = 1;
                    $responseData['acftkn'] = ['acftkname' => csrf_token(), 'acftknhs'  => csrf_token()];
                    return response()->json($responseData);
                }
                return response()->json(['status' => 2, 'message' => "Requested record not found."], 442);
            } catch (\Exception $e) {
                return response()->json(['status' => 0, 'message' => "Decryption failure."], 400);
            }
        }
        return response()->json(['status' => 2, 'message' => "Parameters missing."], 400);
    }

    public function toggleTrash(Request $request): JsonResponse
    {
        try {
            $id = base64_decode($request->input('id'), true);

            $user = User::findOrFail($id);

            $targetAction = (int) $request->input('action_type', 0);

            DB::beginTransaction();

            $previousData = [
                'trash' => $user->trash,
            ];

            $user->update([
                'trash' => $targetAction,
            ]);

            if (function_exists('track_activity')) {
                track_activity(
                    $previousData,
                    $this->users,
                    [
                        'trash' => $targetAction,
                    ],
                    $id,
                    'users',
                    1
                );
            }

            DB::commit();

            $this->clearUserCache($id);

            $message = $targetAction === 1
                ? 'Record dropped into trash logs safely.'
                : 'Record trace restored back successfully.';

            return response()->json([
                'status'  => 1,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 0,
                'message' => 'State processing system transaction failure.',
            ], 500);
        }
    }

    private function clearUserCache(?int $userId = null): void
    {
        Cache::forget('count_active');
        Cache::forget('count_inactive');
        Cache::forget('count_total');
        Cache::forget('dt_total_base');
        Cache::forget('users_all_count');
        Cache::forget('users_inactive_count');
        Cache::forget('users_active_count');
        Cache::forget('users_list_data');

        if ($userId) {
            Cache::forget("user_details_{$userId}");
        }
    }
}
