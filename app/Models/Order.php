<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{

    use HasFactory;

    protected $primaryKey = 'id';  // Si el campo `id` es diferente en la base de datos
    public $incrementing = false;   // Si el ID no es auto-incremental
    protected $keyType = 'string';  // Asegúrate de que el tipo sea string

    protected $fillable = [
        'client',
        'status',
        'comment',
        'total_amount',
        'date',
        'id'
    ];
    public $timestamps = false;

}