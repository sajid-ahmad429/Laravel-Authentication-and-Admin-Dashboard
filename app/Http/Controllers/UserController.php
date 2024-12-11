<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;


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
        // Save the JSON data to a file
        $this->saveDataToJsonFile();

        $data['active'] = $this->users->activeCount();
        $data['inactive'] = $this->users->inactiveCount();
        $data['totalUsers'] = $this->users->getAllCount();
        return view('masters.users.list',$data);
    }

    public function store(Request $request)
    {
        $returnData = [
            'status' => 0,
            'message' => 'Failed',
            'acftkn' => [
                'acftkname' => csrf_token(), // Include the CSRF token
                'acftknhs' => csrf_token() // Include the CSRF token
            ]
        ];

        // Determine if we are updating an existing user
        $isUpdating = $request->has('user_id') && $request->input('user_id') != '' && $request->input('user_id') != 0;

        // Define validation rules
        $rules = [
            'userFullname' => 'required|string|max:255|regex:/^[a-zA-Z\s\-]+$/',
            'userEmail' => 'required|email' . ($isUpdating ? '' : '|unique:users,email'), // Unique email validation for new users
            'userContact' => 'required|string|max:10' . ($isUpdating ? '' : '|unique:users,contact_no'), // Unique contact_no validation for new users
        ];
        // Validate the request
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            // Return JSON response with validation errors
            $responseData['validation'] = $validator->errors();
            $responseData['status'] = 2;
            $responseData['message'] = 'Failed To Add User. Please Check All Fields Are Filled Properly.';
            return response()->json($responseData);
        }

        // Check for duplicate records
        if (!$isUpdating) {
            // Check for duplicate email and contact number
            $duplicateUser = User::where('email', $request->input('userEmail'))
                                ->orWhere('contact_no', $request->input('userContact'))
                                ->first();
            if ($duplicateUser) {
                $message = ($duplicateUser->email === $request->input('userEmail'))
                    ? 'Duplicate entry: Email Address Already Registered'
                    : 'Duplicate entry: Mobile Number Already in Use';

                $returnData['message'] = $message;
                $returnData['status'] = 2;
                return response()->json($returnData);
            }
        } else {
            // When updating, check for duplicates excluding the current user
            $userId = $request->input('user_id');
            $duplicateUser = User::where(function ($query) use ($request, $userId) {
                $query->where('email', $request->input('userEmail'))
                    ->orWhere('contact_no', $request->input('userContact'));
            })
            ->where('id', '!=', $userId) // Exclude the current user
            ->first();

            if ($duplicateUser) {
                $message = ($duplicateUser->email === $request->input('userEmail'))
                    ? 'Duplicate entry: Email Address Already Registered'
                    : 'Duplicate entry: Mobile Number Already in Use';

                $returnData['message'] = $message;
                $returnData['status'] = 2;
                return response()->json($returnData);
            }
        }

        try {
            // Prepare data array
            $data = [
                'name' => $request->input('userFullname'),
                'email' => $request->input('userEmail'),
                'password' => bcrypt('Smart@#123'), // Hash the password
                'contact_no' => $request->input('userContact'),
                'company_name' => $request->input('companyName'),
                'country' => $request->input('country'),
                'roles' => $request->input('user-role'),
                'plan' => $request->input('user-plan'),
            ];

            if ($isUpdating) {
                // Update existing user
                $user = User::find($request->input('user_id'));
                if ($user) {
                    $previousUpdateData = [];
                    $select_array = array_keys($data);  // Get the keys of the data being updated
                    $previousUpdateData = $this->users->select($select_array)->where('id', $userId)->first();
                    if ($previousUpdateData) {
                        $previousUpdateData = $previousUpdateData->toArray();  // Convert object to array
                    }
                    $user->update($data);
                    track_activity($previousUpdateData, $this->users, $data, $userId, 'users', 1);
                    // $this->saveDataToJsonFile(); // Save data to JSON file after updating
                    $returnData['status'] = 1;
                    $returnData['message'] = 'Record Details Updated Successfully';
                    return response()->json($returnData);
                } else {
                    $returnData['status'] = 2;
                    $returnData['message'] = 'User not found';
                    return response()->json($returnData);
                }
            } else {
                // Create a new user
                User::create($data);
                // $this->saveDataToJsonFile(); // Save data to JSON file after creating
                return response()->json([
                    'status' => 1,
                    'message' => 'Record Details Added Successfully'
                ], 201); // Created
            }
        } catch (\Exception $e) {
            // Log the exception message
            \Log::error('Error creating or updating user: ' . $e->getMessage());

            // Return JSON response with error message
            return response()->json([
                'status' => 0,
                'message' => 'An error occurred while processing the request. Please try again.'
            ], 500); // Internal Server Error
        }
    }


    public function getData(Request $request)
    {
        // Ensure this method is only accessible via AJAX if required
        if ($request->ajax()) {
            // Fetch and format user data
            $data = User::select('id', 'name', 'email', 'roles', 'contact_no', 'country', 'company_name', 'plan', 'status', 'activated')->get();

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

            // Save the JSON data to a file
            $this->saveDataToJsonFile();

            // Return JSON response for DataTables or as needed
            return response()->json([
                'data' => $formattedData
            ]);
        }

        // Optionally handle non-AJAX requests or return an error
        return response()->json([
            'status' => 0,
            'message' => 'Invalid request'
        ], 400); // Bad Request
    }

    private function saveDataToJsonFile()
    {
        $data = User::select('id', 'name', 'email', 'roles', 'contact_no', 'country', 'company_name', 'plan', 'status', 'activated')->get();

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

        $jsonData = ['data' => $formattedData];
        $jsonFilePath = public_path('assets/json/user-list.json');

        // Save JSON data to file
        File::put($jsonFilePath, json_encode($jsonData));
    }

    public function getUserDetails(Request $request)
    {
        $returnData = [
            'status' => 0,
            'message' => 'Failed',
            'acftkn' => [
                'acftkname' => csrf_token(), // Include the CSRF token
                'acftknhs' => csrf_token() // Include the CSRF token
            ]
        ];
        // Check if the user is authenticated and has the correct role
        if ($request->has('id')) {
            $id = base64_decode($request->input('id'));

            // Fetch data from the database
            $usersData = User::where(['status' => 1,'id' => $id])->first();

            if ($usersData) {
                // Add the CSRF token to the response data
                $responseData = $usersData->toArray();
                $responseData['acftkn'] = [
                    'acftkname' => csrf_token(), // Include the CSRF token
                    'acftknhs' => csrf_token() // Include the CSRF token
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
