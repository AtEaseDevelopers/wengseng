<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ProductBalanceQuantity extends Model
{
    // Specify the fillable attributes for mass assignment
    protected $guarded = [];

    public static $attribute_rules = [
        "date" => [
            'required', 
            'date_format:Y-m-d',
        ],
    ];

    // Define the belongsTo relationship to Category
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}