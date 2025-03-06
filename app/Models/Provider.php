<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Provider extends Model
{
    use HasFactory;

    public $incrementing = false; // Importante: evita que Laravel lo trate como auto-increment
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'provider_code',
        'contact_info',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($provider) {
            if (empty($provider->id)) {
                $provider->id = Str::uuid();
            }
        });
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_providers')
            ->withPivot('purchase_price', 'provider_url')
            ->withTimestamps();
    }
}
