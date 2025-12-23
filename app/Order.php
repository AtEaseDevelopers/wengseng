<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    // Specify the fillable attributes for mass assignment
    protected $fillable = [
        'user_id', 
        'cart_id', 
        'total_price', 
        'attn_name', 
        'attn_contact', 
        'pricing_date', 
        'delivering_date', 
        'area', 
        'billing_address', 
        'billing_city',
        'billing_postcode', 
        'billing_state', 
        'shipping_address', 
        'shipping_city', 
        'shipping_postcode', 
        'shipping_state', 
        'payment_method', 
        'transfer_slip', 
        'status',
        'driver_id',
        'order_weight',
        'do_no',
        'do_date'
    ];

    public static $path = 'orders';
    
    public static $attribute_rules = [
        'attn_name' => ['nullable', 'string', 'max:30'],
        'attn_contact' => ['nullable', 'string', 'max:30'],
        'billing_address' => ['required', 'string', 'max:100'],
        'billing_postcode' => ['required', 'string', 'max:5'],
        'billing_state' => ['required', 'string', 'max:30'],
        'shipping_address' => ['nullable', 'string', 'max:100'],
        'shipping_postcode' => ['nullable', 'string', 'max:5'],
        'shipping_state' => ['nullable', 'string', 'max:30'],
        'payment_method' => ['required'],
        'transfer_slip' => ['nullable', 'required_if:payment_method,bank-transfer', 'mimes:jpg,jpeg,png', 'max:4096'],
    ];

    public static $status = [
        // 'pending' => 'pending', // removed pending status as per requested on 16/11/2023
        'cancelled' => 'cancelled',
        'processing' => 'processing',
        // 'delivering' => 'delivering',
        'completed' => 'completed',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public static function generateDoNumber()
    {
        $do_prefix         = Config::where('key', 'DO_PREFIX')->value('value') ?? 'DO-';
        $do_format         = Config::where('key', 'DO_FORMAT')->value('value') ?? '';
        $do_running_number = Config::where('key', 'DO_RUNNING_NUMBER')->value('value') ?? (date('YmdHis') . mt_rand(1000, 9999));
              
        return $do_prefix . '_' . $do_format . '_' . $do_running_number;
    }

    public static function updateDoNumberByOrderId($order)
    {
        $do_number = self::generateDoNumber();
        if ($do_number != null) {
            $order->do_no   = $do_number;
            $order->do_date = date('Y-m-d');
            $order->update();

            return true;
        }
        return false;
    }

    public function updateOrderStatus($order, $status)
    {
        $order->status = $status;
        $order->update();
        return true;
    }
    
    public static function getOrdersWithUser(array $orderIds)
    {
        return DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->whereIn('orders.id', $orderIds)
            ->select(
                'orders.*',
                'users.name as user_name',
                'users.email as user_email',
                'users.sql_customer_code'
            )
            ->get();
    }

   public static function getCartItemsForOrders(array $cartIds)
    {
        return DB::table('cart_products')
            ->join('products', 'cart_products.product_id', '=', 'products.id')
            ->leftJoin('uoms', 'products.uom_id', '=', 'uoms.id')
            ->whereIn('cart_products.cart_id', $cartIds)
            ->select(
                'cart_products.cart_id',
                'cart_products.product_id',
                'cart_products.quantity as quantity',
                'cart_products.weight as weight',
                'cart_products.unit_price as unit_price',
                'cart_products.price as price',
                'cart_products.remark as remark',
                'products.name as product_name',
                'products.sku as product_sku',
                'uoms.uom_name as uom_name'
            )
            ->get()
            ->groupBy('cart_id'); // this groups in PHP, not SQL
    }

    public static function prepareSyncOrders(array $orderIds)
    {
        $orders = self::getOrdersWithUser($orderIds);
        $cartIds = $orders->pluck('cart_id')->filter()->unique()->all();
        $cartItemsMap = self::getCartItemsForOrders($cartIds);

        return $orders->map(function ($order) use ($cartItemsMap) {
            return [
                'id' => $order->id,
                'do_no' => 'IV ' . now()->format('ym') . '-' . $order->id,
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
        })->keyBy('id');
    }

}