<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderProducts extends Model
{
    use HasFactory;
    protected $table = 'products_order';
    protected $primaryKey = 'order_id';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'picture',
        'price',
        'product_id',
        'order_id',
        'quantity',
        'subtotal',
    ];
    public $timestamps = false;

}