<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{

    use HasFactory;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'id'
    ];

    public $timestamps = false;

    public function orders()
    {
        return $this->hasMany(Order::class, 'client', 'id');
    }

    protected static function booted()
    {
        static::deleting(function ($client) {
            $client->orders()->delete();
        });
    }   

}