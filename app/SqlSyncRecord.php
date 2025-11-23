<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
}