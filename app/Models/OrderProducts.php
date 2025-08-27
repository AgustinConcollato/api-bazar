<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderProducts extends Model
{
    use HasFactory;
    protected $table = 'product_order';

    protected $fillable = [
        'name',
        'picture',
        'purchase_price',
        'price',
        'product_id',
        'discount',
        'order_id',
        'quantity',
        'subtotal',
    ];

    protected $hidden = [
        'purchase_price'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}