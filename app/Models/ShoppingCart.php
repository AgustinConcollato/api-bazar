<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ShoppingCart extends Model
{

    use HasFactory;

    protected $table = 'shopping_cart';
    public $incrementing = false;

    protected $fillable = [
        'client_id',
        'product_id',
        'quantity'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::uuid();
            }
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

}