<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SqlSyncRecord extends Model
{
    protected $table = 'sql_sync_records';

    protected $fillable = [
        'target_id',
        'action',
        'target_name',
        'details',
        'response',
        'status',
        'remark',
    ];

    protected $casts = [
        'details' => 'array',
        'response' => 'array',
    ];

    /**
     * Expire old active sync jobs for the same target & action
     */
    public static function expireOld($targetId, $action, $targetName)
    {
        return self::where('target_id', $targetId)
            ->where('action', $action)
            ->where('target_name', $targetName)
            ->where('status', 'pending')
            ->update([
                'status' => 'expired',
                'remark' => 'Replaced by new sync request',
            ]);
    }

    /**
     * Create pending sync job after expiring old ones
     */
    public static function queue($data)
    {
        self::expireOld(
            $data['target_id'],
            $data['action'],
            $data['target_name']
        );

        return self::create(array_merge($data, [
            'status' => 'pending',
            'remark' => 'Waiting to sync queue execution',
        ]));
    }
}
