<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductDefaultPricing extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'customer_category_id',
        'price',
    ];
}
