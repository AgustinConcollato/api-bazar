<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShoppingCart extends Model
{

    use HasFactory;

    protected $table = 'shopping_cart';
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'product_id',
        'quantity'
    ];

    // ShoppingCart.php
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

}