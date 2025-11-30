<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use App\SqlSyncRecord;
use Illuminate\Http\Request;
use App\Order;
class SqlSyncRecordController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'target_id'   => 'required|string',
            'action'      => 'required|string',
            'target_name' => 'required|string',
            'details'     => 'required|array',
            'status'      => 'required|in:pending,success,failed',
            'remark'      => 'nullable|string',
            'response'    => 'nullable|array',
        ]);

        $data = $request->all();

        if ($validator->fails()) {
            // Get only the error messages
            $errors = $validator->errors()->messages();

            // Save the entire input as JSON string in remark
            $data['remark'] = json_encode($data);

            // Save only the error messages in response (as array)
            $data['response'] = $errors;

            // Optionally, set status to failed if validation failed
            $data['status'] = 'failed';
        }

        $record = SqlSyncRecord::where('target_id', $data['target_id'] ?? null)
            ->where('target_name', $data['target_name'] ?? null)
            ->where('action', $data['action'] ?? null)
            ->first();

        if ($record) {
            $record->update([
                'status'   => $data['status'] ?? $record->status,
                'response' => $data['response'] ?? $record->response,
                'remark'   => $data['remark'] ?? $record->remark,
                'details'  => $data['details'] ?? $record->details,
            ]);
        } else {
            $record = SqlSyncRecord::create([
                'target_id'   => $data['target_id'] ?? null,
                'action'      => $data['action'] ?? null,
                'target_name' => $data['target_name'] ?? null,
                'details'     => $data['details'] ?? [],
                'status'      => $data['status'] ?? 'failed',
                'remark'      => $data['remark'] ?? null,
                'response'    => $data['response'] ?? null,
            ]);
        }

        return response()->json(['success' => true, 'record' => $record], 201);
    }


    public function index()
    {
        // Get pending sync records only
        $records = SqlSyncRecord::where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->take(env('SQL_SYNC_RECORD_LIMIT')) // optional: limit only pending
            ->get();

        if ($records->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No pending records found',
                'data' => []
            ]);
        }

        // Extract order IDs from queued jobs
        $orderIds = $records->pluck('target_id')->filter()->unique()->values()->toArray();

        // Prepare actual order data using your helper
        $ordersData = Order::prepareSyncOrders($orderIds);

        // Combine the two datasets into a single API response
        $responseData = $records->map(function ($record) use ($ordersData) {
            $orderId = $record->target_id;

            return [
                'record_id' => $record->id,
                'action' => $record->action,
                'status' => $record->status,
                'created_at' => $record->created_at,
                'order' => $ordersData[$orderId] ?? null,
            ];
        });

        return response()->json([
            'status' => 'success',
            'pending_count' => count($responseData),
            'data' => $responseData,
        ]);
    }

}
