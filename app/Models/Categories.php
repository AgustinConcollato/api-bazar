<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categories extends Model
{
    use HasFactory;

    public function subcategories()
    {
        return $this->hasMany(Subcategories::class, 'category_code', 'category_code');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id', 'category_code');
    }
}
