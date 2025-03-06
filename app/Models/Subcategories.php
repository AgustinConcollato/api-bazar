<?php

namespace App\Models;
use App\Models\Categories;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subcategories extends Model
{
    use HasFactory;

    protected $table = 'subcategories';
    
    protected $fillable = [
        'user_id',
        'product_id',
        'quantity'
    ];
    public function category()
    {
        return $this->belongsTo(Categories::class, 'category_code', 'category_code');
    }
}
