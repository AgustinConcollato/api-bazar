<?php

namespace App\Models;
use App\Models\Categories;
use Illuminate\Database\Eloquent\Model;

class Subcategories extends Model
{
    public function category()
    {
        return $this->belongsTo(Categories::class, 'category_code', 'category_code');
    }
}
