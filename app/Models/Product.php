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
        'category_id', // Agrega esto
        'subcategory', // Asegúrate de que este campo también esté aquí
        'available_quantity', // Y este campo
        'status',
        'creation_date',
        'code',
        'id',
        'images',
        'thumbnails',
        // Agrega otros campos según sea necesario
    ];

    public $timestamps = false;
}



