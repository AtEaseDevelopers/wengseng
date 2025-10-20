<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\InvoiceSyncService;
use App\Order;
use App\LogAction;

class SyncInvoices extends Command
{
    protected $signature = 'sync:saleInvoice';
    protected $description = 'Sync processing Invoice into SQL Accounting';

    public function handle()
    {
        $log = LogAction::log([
            'action_name'   => 'SyncInvoiceCommand',
            'action_ref_no' => '',
            'request'       => [], // CLI has no HTTP request
            'headers'       => [],
            'body'          => [], // optional: maybe $orders->pluck('id')
            'remark'        => 'Sale Invoice sync via console command started',
        ]);

        $orderIds = Order::where('status', 'processing')
                ->whereNotNull('do_no')
                ->where('sql_sync_status', '!=', 'SUCCESS')
                ->pluck('id')
                ->all(); // → gives you array of IDs

        $orders = Order::getOrdersWithUser($orderIds);
        $cartIds = $orders->pluck('cart_id')->filter()->unique()->all();

        $cartItemsMap = Order::getCartItemsForOrders($cartIds);

        $allOrders = $orders
            ->map(function ($order) use ($cartItemsMap) {
                return [
                    'id' => $order->id,
                    'do_no' => $order->do_no,
                    'do_date' => $order->do_date,
                    'attn_name' => $order->attn_name,
                    'attn_contact' => $order->attn_contact,
                    'billing_address' => $order->billing_address,
                    'payment_method' => $order->payment_method,
                    'sql_sync_status' => $order->sql_sync_status,
                    'sql_sync_respond' => $order->sql_sync_respond,
                    'user_name' => $order->user_name,
                    'user_email' => $order->user_email,
                    'sql_customer_code' => $order->sql_customer_code,
                    'status' => $order->status,
                    'cart_id' => $order->cart_id,
                    'items' => $cartItemsMap[$order->cart_id] ?? [],
                ];
            })->keyBy('id'); // This sets 'id' as the key for $allOrders

        $validOrders = [];
        foreach ($orderIds as $id) {
            if (!isset($allOrders[$id])) {
                continue;
            }
            $order = $allOrders[$id];
            $validOrders[$id] = $order;
        }

        $results = app(InvoiceSyncService::class)->sync($validOrders);

        $responds = [
            'orders' => $orders,
            'results' => $results,
        ];

        LogAction::updateLogResponse($log->id, null, $responds, 'SyncInvoiceCommand completed');
        $this->info("Sync completed.");
    }
}
