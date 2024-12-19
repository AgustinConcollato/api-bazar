<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'city',
        'address',
        'province',
        'zip_code',
        'status',
        'code',
        'address_number'
    ];

    public $timestamps = false;

}