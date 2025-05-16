<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignProduct extends Model
{
    protected $fillable = ['campaign_id', 'product_id', 'custom_discount_type', 'custom_discount_value'];
    
    protected $table = 'campaign_product';

    public function campaign()
    {
        return $this->belongsTo(Campaigns::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
