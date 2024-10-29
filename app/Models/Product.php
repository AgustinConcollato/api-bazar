<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'category_id',
        'subcategory',
        'available_quantity',
        'status',
        'creation_date',
        'code',
        'id',
        'images',
        'thumbnails',
        'last_date_modified'
    ];

    public $timestamps = false;
}



