<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Campaigns extends Model
{

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'discount_type',
        'discount_value',
        'start_date',
        'end_date',
        'is_active',
        'image'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'campaign_product', 'campaign_id', 'product_id')
            ->withPivot('custom_discount_type', 'custom_discount_value');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($campaign) {
            $campaign->slug = Str::slug($campaign->name);
            $campaign->id = Str::uuid();
        });
    }
}