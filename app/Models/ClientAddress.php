<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientAddress extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';

    protected $table = 'client_address';

    protected $fillable = [
        'client_id',
        'city',
        'address',
        'province',
        'zip_code',
        'status',
        'code',
        'address_number'
    ];
}