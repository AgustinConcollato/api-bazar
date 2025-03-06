<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';  // Si el campo `id` es diferente en la base de datos
    public $incrementing = false;   // Si el ID no es auto-incremental
    protected $keyType = 'string';  // Asegúrate de que el tipo sea string

    protected $fillable = [
        'name',
        'description',
        'price',
        'discount',
        'category_code',
        'subcategory_code',
        'available_quantity',
        'status',
        'code',
        'images',
        'thumbnails'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->id)) {
                $order->id = Str::uuid();
            }
        });
    }

    public function providers()
    {
        return $this->belongsToMany(Provider::class, 'product_providers')
            ->withPivot('purchase_price', 'provider_url')
            ->withTimestamps();
    }

}



