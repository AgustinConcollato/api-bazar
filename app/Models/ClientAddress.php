<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ClientAddress extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'client_address';

    protected $fillable = [
        'client_id',
        'city',
        'address',
        'province',
        'zip_code',
        'status',
        'address_number'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::uuid();
            }
        });
    }

}