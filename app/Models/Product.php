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
        'price_final',
        'discount',
        'category_code',
        'subcategory_code',
        'available_quantity',
        'status',
        'code',
        'images',
        'thumbnails'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'price_final' => 'decimal:2',
        'discount' => 'integer',
    ];

    // Método para obtener precio según tipo de cliente
    public function getPriceForClient($clientType = 'final'): float
    {
        if ($clientType === 'reseller') {
            return (float) $this->price; // Precio mayorista
        }

        // Para consumidor final, usar price_final si existe, sino calcular 10% más
        if ($this->price_final) {
            return (float) $this->price_final;
        }

        // Si no tiene price_final, calcular 15% más sobre el precio mayorista
        return (float) ($this->price * 1.15);
    }


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

    public function orderProducts()
    {
        return $this->hasMany(OrderProducts::class);
    }
}
