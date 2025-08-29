<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{

    use HasFactory;
    protected $primaryKey = 'id';  // Si el campo `id` es diferente en la base de datos
    public $incrementing = false;   // Si el ID no es auto-incremental
    protected $keyType = 'string';  // Asegúrate de que el tipo sea string

    protected $fillable = [
        'client_id',
        'status',
        'comment',
        'discount',
        'total_amount',
        'address',
        'client_name',
        'delivery',
        'surcharge'
    ];


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->id)) {
                $order->id = Str::uuid();
            }
        });
    }

    protected static function booted()
    {
        static::deleting(function ($order) {
            $order->products()->delete();
        });
    }

    public function products()
    {
        return $this->hasMany(OrderProducts::class, 'order_id', 'id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'order_id', 'id');
    }

    // Cuánto falta pagar
    public function remainingAmount(): float
    {
        if ($this->created_at < '2025-06-11') {
            return 0;
        }
        $totalPaid = $this->payments->sum('paid_amount');
        return max(0, $this->total_amount - $totalPaid);
    }
}