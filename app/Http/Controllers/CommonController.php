<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Schema;

class CommonController extends Controller
{
    protected $session;
    protected $db;

    public function __construct()
    {
        $this->session = Session::getFacadeRoot();
        $this->db = DB::connection();
    }

    public function chnage_status(Request $request) {
        $id = base64_decode($request->input('id'));
        $status = $request->input('status');
        $tableName = base64_decode($request->input('name'));
        $fieldNames = Schema::getColumnListing($tableName);

        $result = false; // Initialize the result variable
        $message = '';   // Initialize the message variable
        $previousUpdateData = $this->db->table($tableName)->select($fieldNames)->where('id', $id)->first();

        // Check if the result is not null and convert to an array
        if ($previousUpdateData) {
            $previousUpdateData = (array) $previousUpdateData; // Convert stdClass to array
        } else {
            $previousUpdateData = [];
        }

        if ($tableName == 'users' && $status == 2) {
            // Delete operation
            $result = DB::table($tableName)->where('id', $id)->delete();
            $additionalFields = ['deleted_by' => session('id'), 'deleted_at' => now(),'change_status' => $status,];
            $data = array_merge(['id' => $id, 'status' => $status, 'trash' => 1], $additionalFields);
            // $data = constructDataArray($tableName, $id, $status, $additionalFields);
            track_activity($previousUpdateData, "", $data, $id, $tableName, 4);
            $message = $result ? 'The record has been deleted successfully.' : 'Failed to delete the record.';
        } else {
            if ($status == 2) {
                // Move to trash
                $result = DB::table($tableName)->where('id', $id)->update(['status' => $status, 'trash' => 1]);
                $additionalFields = ['deleted_by' => session('id'), 'deleted_at' => now()];
                $data = array_merge(['id' => $id, 'status' => $status, 'trash' => 1], $additionalFields);
                // $data = constructDataArray($tableName, $id, $status, $additionalFields);
                track_activity($previousUpdateData, "", $data, $id, $tableName, 3);
                $message = $result ? 'The record has been moved to trash.' : 'Failed to move the record to trash.';
            } else {
                // Update status
                $result = DB::table($tableName)->where('id', $id)->update(['status' => $status]);
                $additionalFields = ['status_change_by' => session('id'), 'deleted_at' => now()];
                $data = array_merge(['id' => $id, 'status' => $status], $additionalFields);
                // $data = constructDataArray($tableName, $id, $status, $additionalFields);
                track_activity($previousUpdateData, "", $data, $id, $tableName, $status == 0 ? 3 : 2);
                $message = $result ? 'The record has been updated successfully.' : 'Failed to update the record.';
            }
        }

        // Return JSON response with success indicator and message
        return response()->json([
            'success' => $result ? '1' : '0',
            'message' => $message
        ]);
    }


}
