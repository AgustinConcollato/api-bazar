<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderProducts extends Model
{
    use HasFactory;
    protected $table = 'products_order';
    protected $primaryKey = 'count';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'picture',
        'price',
        'product_id',
        'discount',
        'order_id',
        'quantity',
        'subtotal',
    ];
    public $timestamps = false;

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}