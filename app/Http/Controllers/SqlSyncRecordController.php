<?php

namespace App\Http\Controllers;

use App\SqlSyncRecord;
use Illuminate\Http\Request;
use App\Order;
class SqlSyncRecordController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'target_id'   => 'required|string',
            'action'      => 'required|string',
            'target_name' => 'required|string',
            'details'     => 'required|array',
            'status'      => 'required|in:pending,success,failed',
            'remark'      => 'nullable|string',
            'response'    => 'nullable|array',
        ]);

        // Find existing record with same target_id, target_name, and action
        $record = SqlSyncRecord::where('target_id', $data['target_id'])
            ->where('target_name', $data['target_name'])
            ->where('action', $data['action'])
            ->first();

        if ($record) {
            // Update existing record
            $record->update([
                'status'   => $data['status'],
                'response' => $data['response'] ?? $record->response,
                'remark'   => $data['remark'] ?? $record->remark,
                'details'  => $data['details'],
            ]);
        } else {
            // Create new record if not found
            $record = SqlSyncRecord::create($data);
        }

        return response()->json(['success' => true, 'record' => $record], 201);
    }


   public function index()
    {
        // Get pending sync records only
        $records = SqlSyncRecord::where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->take(20) // optional: limit only pending
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
