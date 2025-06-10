<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CashRegister extends Model
{
    use HasFactory;
    protected $fillable = [
        'method',
        'amount',
        'total_amount',
        'type',
        'description',
        'payment_id'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'total_amount' => 'decimal:2'
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
