<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CashRegister extends Model
{
    use HasFactory;
    protected $fillable = [
        'date',
        'methos',
        'amount',
        'type',
        'description',
        'payment_id'
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = Str::uuid();
        });
    }
}
