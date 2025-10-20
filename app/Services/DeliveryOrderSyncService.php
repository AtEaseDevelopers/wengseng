<?php

namespace App\Services;

use App\Order;
use App\Services\SqlAccountingService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\LogAction;

class DeliveryOrderSyncService
{
    public function sync($orders)
    {
        $results = [];
        $service = new SqlAccountingService();

        foreach ($orders as $order) {
            $log = LogAction::log([
                'action_name'   => 'SyncDeliveryOrder',
                'action_ref_no' => $order['do_no'],
                'request' => app()->runningInConsole() ? [] : request()->all(),
                'headers' => app()->runningInConsole() ? [] : request()->headers->all(),
                'body'          => $order,
                'remark'        => 'Sync started',
            ]);

            // Initialize response and remark
            $sync_do = null;
            $responseLog = null;
            $remark = null;

            try {
                $sync_do = $service->PostDataDOWithOrderData($order);

                DB::table('orders')->where('id', $order['id'])->update([
                    'status'            => 'completed',
                    'do_no'             => $sync_do,
                    'sql_sync_status'   => 'SUCCESS',
                    'sql_sync_message'  => '',
                    'updated_at'        => now(),
                ]);

                $responseLog = [
                    'status'  => 'SUCCESS',
                    'do_no'   => $sync_do,
                ];

                $remark = 'Sync completed';

                $results[$order['id']] = [
                    'status'  => 'success',
                    'message' => "Synced successfully (DO: {$sync_do})"
                ];
            } catch (\Throwable $e) {
                DB::table('orders')->where('id', $order['id'])->update([
                    'sql_sync_status'   => 'ERROR',
                    'sql_sync_message'  => $e->getMessage(),
                    'updated_at'        => now(),
                ]);

                $responseLog = [
                    'status'  => 'ERROR',
                    'message' => $e->getMessage(),
                ];

                $remark = 'Sync failed';

                $results[$order['id']] = [
                    'status'  => 'failed',
                    'message' => $e->getMessage(),
                ];
                LogAction::updateLogResponse($log->id, $order['do_no'], $responseLog, $remark);
                continue;
            }

            LogAction::updateLogResponse($log->id, $order['do_no'], $responseLog, $remark);
        }

        return $results;
    }


}
