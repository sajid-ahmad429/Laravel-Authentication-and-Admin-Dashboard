<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    protected $user;
    
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
        
        // Save the JSON data to a file (optimized)
        $this->saveDataToJsonFile();

        return view('masters.users.list', $data);
    }

    public function store(Request $request)
    {
        $returnData = [
            'status' => 0,
            'message' => 'Failed',
            'acftkn' => [
                'acftkname' => csrf_token(),
                'acftknhs' => csrf_token()
            ]
        ];

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
            $responseData['validation'] = $validator->errors();
            $responseData['status'] = 2;
            $responseData['message'] = 'Failed To Add User. Please Check All Fields Are Filled Properly.';
            return response()->json($responseData);
        }

        // Check for duplicate records with optimized queries
        if (!$isUpdating) {
            // Single query to check for duplicates
            $duplicateUser = User::where(function ($query) use ($request) {
                $query->where('email', $request->input('userEmail'))
                      ->orWhere('contact_no', $request->input('userContact'));
            })->first(['email', 'contact_no']);

            if ($duplicateUser) {
                $message = ($duplicateUser->email === $request->input('userEmail'))
                    ? 'Duplicate entry: Email Address Already Registered'
                    : 'Duplicate entry: Mobile Number Already in Use';

                $returnData['message'] = $message;
                $returnData['status'] = 2;
                return response()->json($returnData);
            }
        } else {
            // Optimized update duplicate check
            $userId = $request->input('user_id');
            $duplicateUser = User::where(function ($query) use ($request, $userId) {
                $query->where('email', $request->input('userEmail'))
                      ->orWhere('contact_no', $request->input('userContact'));
            })
            ->where('id', '!=', $userId)
            ->first(['email', 'contact_no']);

            if ($duplicateUser) {
                $message = ($duplicateUser->email === $request->input('userEmail'))
                    ? 'Duplicate entry: Email Address Already Registered'
                    : 'Duplicate entry: Mobile Number Already in Use';

                $returnData['message'] = $message;
                $returnData['status'] = 2;
                return response()->json($returnData);
            }
        }

        // Use database transaction for data integrity
        DB::beginTransaction();
        
        try {
            // Prepare data array
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
                // Update existing user with optimized query
                $user = User::find($request->input('user_id'));
                if ($user) {
                    // Get previous data for activity tracking
                    $select_array = array_keys($data);
                    $previousUpdateData = $user->only($select_array);
                    
                    $user->update($data);
                    track_activity($previousUpdateData, $this->users, $data, $userId, 'users', 1);
                    
                    DB::commit();
                    
                    $returnData['status'] = 1;
                    $returnData['message'] = 'Record Details Updated Successfully';
                    return response()->json($returnData);
                } else {
                    DB::rollBack();
                    $returnData['status'] = 2;
                    $returnData['message'] = 'User not found';
                    return response()->json($returnData);
                }
            } else {
                // Create a new user
                User::create($data);
                DB::commit();
                
                return response()->json([
                    'status' => 1,
                    'message' => 'Record Details Added Successfully'
                ], 201);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating or updating user: ' . $e->getMessage());

            return response()->json([
                'status' => 0,
                'message' => 'An error occurred while processing the request. Please try again.'
            ], 500);
        }
    }

    public function getData(Request $request)
    {
        if ($request->ajax()) {
            // Use optimized cached data retrieval
            $data = User::getListData();

            $formattedData = $data->map(function ($user) {
                return [
                    'id' => $user->id,
                    'full_name' => ucwords($user->name),
                    'role' => ucwords($user->roles),
                    'email' => $user->email,
                    'current_plan' => ucwords($user->plan),
                    'country' => $user->country,
                    'status' => $user->status,
                    'activated' => $user->activated,
                ];
            });

            return response()->json([
                'data' => $formattedData
            ]);
        }

        return response()->json([
            'status' => 0,
            'message' => 'Invalid request'
        ], 400);
    }

    private function saveDataToJsonFile()
    {
        // Use cached data to avoid redundant database queries
        $cacheKey = 'user_json_data';
        $jsonData = Cache::remember($cacheKey, 300, function () {
            $data = User::getListData();

            $formattedData = $data->map(function ($user) {
                return [
                    'id' => $user->id,
                    'full_name' => ucwords($user->name),
                    'role' => ucwords($user->roles),
                    'email' => $user->email,
                    'current_plan' => ucwords($user->plan),
                    'country' => $user->country,
                    'status' => $user->status,
                    'activated' => $user->activated,
                ];
            });

            return ['data' => $formattedData];
        });

        $jsonFilePath = public_path('assets/json/user-list.json');

        // Only write to file if data has changed
        if (!File::exists($jsonFilePath) || File::get($jsonFilePath) !== json_encode($jsonData)) {
            File::put($jsonFilePath, json_encode($jsonData));
        }
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

            // Use cache for frequently accessed user details
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
