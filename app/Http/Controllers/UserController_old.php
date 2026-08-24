<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Datatables\usersDataTable;


class UserController extends Controller
{
    protected $users;

    public function __construct(User $user)
    {
        $this->users = $user;
        date_default_timezone_set('Asia/Kolkata');
    }

    public function index()
    {
        // Check if user is logged in
        if (!session()->has('isLoggedIn')) {
            return redirect()->to('sysCtrlLogin');
        }

        $data['activeMenu'] = "users";
        $data['assetsJs'] = array('app-user-list');

        // Use cached counts for better performance
        $data['active'] = $this->users->activeCount();
        $data['inactive'] = $this->users->inactiveCount();
        $data['totalUsers'] = $this->users->getAllCount();

        return view('masters.users.list', $data);
    }

    public function store(Request $request)
    {
        // Determine if we are updating an existing user
        $isUpdating = $request->has('user_id') && $request->input('user_id') != '' && $request->input('user_id') != 0;

        // Define validation rules
        $rules = [
            'userFullname' => 'required|string|max:255|regex:/^[a-zA-Z\s\-]+$/',
            'userEmail' => 'required|email' . ($isUpdating ? '' : '|unique:users,email'),
            'userContact' => 'required|string|max:10' . ($isUpdating ? '' : '|unique:users,contact_no'),
        ];

        // Validate the request
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Failed To Add/Update User. Please Check All Fields.');
        }

        // Check for duplicate records manually if needed, or rely on unique validation rules above.
        // Using database transaction for data integrity
        DB::beginTransaction();

        try {
            $data = [
                'name' => $request->input('userFullname'),
                'email' => $request->input('userEmail'),
                'password' => bcrypt('Smart@#123'),
                'contact_no' => $request->input('userContact'),
                'company_name' => $request->input('companyName'),
                'country' => $request->input('country'),
                'roles' => $request->input('user-role'),
                'plan' => $request->input('user-plan'),
            ];

            if ($isUpdating) {
                $userId = $request->input('user_id');
                $user = User::find($userId);
                if ($user) {
                    $select_array = array_keys($data);
                    $previousUpdateData = $user->only($select_array);

                    $user->update($data);
                    track_activity($previousUpdateData, $this->users, $data, $userId, 'users', 1);

                    DB::commit();

                    return redirect()->back()->with('success', 'Record Details Updated Successfully');
                } else {
                    DB::rollBack();
                    return redirect()->back()->with('error', 'User not found')->withInput();
                }
            } else {
                User::create($data);
                DB::commit();

                return redirect()->back()->with('success', 'Record Details Added Successfully');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating or updating user: ' . $e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing the request. Please try again.')->withInput();
        }
    }

    /**
     * New AJAX function specifically structured for DataTables server-side or standard ajax processing.
     */
    public function getTableData(Request $request)
{
    if (!$request->ajax()) {
        return response()->json([
            'status' => 0,
            'message' => 'Invalid Request'
        ], 400);
    }

    $dataTable = new usersDataTable();

    $users = $dataTable->getTableData($request);
    $totalRecords = $dataTable->countTotal();
    $filteredRecords = $dataTable->countFiltered($request);

    // Agar aapke paas active/inactive counts nikalne ke methods hain toh yahan call karein, warna default 0 rakhein
    $totalActiveRecords = method_exists($dataTable, 'countActive') ? $dataTable->countActive() : 0;
    $totalInActiveRecords = method_exists($dataTable, 'countInactive') ? $dataTable->countInactive() : 0;
    $recordsPending = method_exists($dataTable, 'countPending') ? $dataTable->countPending() : 0;

    $data = [];

    foreach ($users as $user) {
        $data[] = [
            'id' => $user->id,
            'full_name' => ucwords($user->name),
            'email' => $user->email,
            'role' => ucwords($user->roles),
            'current_plan' => ucwords($user->plan),
            'country' => $user->country ?? '-',
            'status' => $user->status
        ];
    }

    return response()->json([
        'draw' => intval($request->input('draw')),
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'totalActiveRecods' => $totalActiveRecords,
        'totalInActiveRecods' => $totalInActiveRecords,
        'recordsPending' => $recordsPending,
        'data' => $data
    ]);
}

    public function getUserDetails(Request $request)
    {
        $returnData = [
            'status' => 0,
            'message' => 'Failed',
            'acftkn' => [
                'acftkname' => csrf_token(),
                'acftknhs' => csrf_token()
            ]
        ];

        if ($request->has('id')) {
            $id = base64_decode($request->input('id'));

            $cacheKey = "user_details_{$id}";
            $usersData = Cache::remember($cacheKey, 300, function () use ($id) {
                return User::where(['status' => 1, 'id' => $id])->first();
            });

            if ($usersData) {
                $responseData = $usersData->toArray();
                $responseData['acftkn'] = [
                    'acftkname' => csrf_token(),
                    'acftknhs' => csrf_token()
                ];

                return response()->json($responseData);
            } else {
                $returnData['status'] = 2;
                $returnData['message'] = "Failed Invalid Data...!";
                return response()->json($returnData);
            }
        } else {
            $returnData['status'] = 2;
            $returnData['message'] = "Failed Invalid Request...!";
            return response()->json($returnData);
        }
    }
}
