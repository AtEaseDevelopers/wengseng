<?php

namespace App\Http\Controllers;

use App\SqlSyncRecord;
use Illuminate\Http\Request;

class SqlSyncRecordController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'target_id'   => 'nullable|string',
            'action'      => 'required|string',
            'target_name' => 'nullable|string',
            'details'     => 'required|array',
            'status'      => 'required|in:pending,success,failed',
            'remark'      => 'nullable|string',
            'response'    => 'nullable|array',
        ]);

        $record = SqlSyncRecord::create($data);

        return response()->json(['success' => true, 'record' => $record], 201);
    }

    public function index()
    {
        $records = SqlSyncRecord::orderBy('created_at', 'desc')->paginate(20);

        return response()->json($records);
    }
}
