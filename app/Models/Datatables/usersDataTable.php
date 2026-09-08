<?php

namespace App\Models\Datatables;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class usersDataTable extends Model
{
    protected $table = 'users';

    protected $columns = [
        0 => 'id',
        1 => 'name',
        2 => 'roles',
        3 => 'plan',
        4 => 'country',
        5 => 'status'
    ];

    /**
     * Build the query for DataTables with search, order, and filters
     */
    public function getTableQuery(Request $request)
    {
        $query = self::query()
            ->where('status', '!=', 2)
            ->where('trash', 0);

        // Global Search
        if (!empty($request->input('search.value'))) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('roles', 'LIKE', "%{$search}%")
                  ->orWhere('country', 'LIKE', "%{$search}%");
            });
        }

        // Sorting
        $order = $request->input('order');
        if (!empty($order) && isset($order[0]['column'])) {
            $columnIndex = $order[0]['column'];
            $columnDir = $order[0]['dir'];

            if (isset($this->columns[$columnIndex])) {
                $query->orderBy($this->columns[$columnIndex], $columnDir);
            }
        } else {
            $query->orderBy('id', 'DESC');
        }

        return $query;
    }

    /**
     * Get paginated records for the DataTable
     */
    public function getTableData(Request $request)
    {
        $query = $this->getTableQuery($request);

        // Pagination
        if ($request->has('start') && $request->has('length') && $request->input('length') != -1) {
            $query->skip((int) $request->input('start'))
                  ->take((int) $request->input('length'));
        }

        return $query->get();
    }

    /**
     * Count total filtered records
     */
    public function countFiltered(Request $request)
    {
        return $this->getTableQuery($request)->count();
    }

    /**
     * Count total raw records
     */
    public function countTotal()
    {
        return self::where('status', '!=', 2)->where('trash', 0)->count();
    }
}