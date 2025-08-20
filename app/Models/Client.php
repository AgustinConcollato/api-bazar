<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class Client extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'source',
        'type',
        'email_verified_at',
        'email_verification_code',
        'email_verification_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_code',
        'email_verification_expires_at',
        'source'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'email_verification_expires_at' => 'datetime',
        'type' => 'string',
    ];

    // Constantes para tipos de cliente
    const TYPE_FINAL = 'final';
    const TYPE_RESELLER = 'reseller';

    // Métodos para verificar tipo de cliente
    public function isFinalConsumer(): bool
    {
        return $this->client_type === self::TYPE_FINAL;
    }

    public function isReseller(): bool
    {
        return $this->client_type === self::TYPE_RESELLER;
    }

    // Método para obtener etiqueta legible del tipo
    public function getClientTypeLabel(): string
    {
        return match($this->client_type) {
            self::TYPE_FINAL => 'Consumidor Final',
            self::TYPE_RESELLER => 'Revendedor',
            default => 'Desconocido'
        };
    }

    // Método para cambiar tipo de cliente
    public function setClientType(string $type): bool
    {
        if (in_array($type, [self::TYPE_FINAL, self::TYPE_RESELLER])) {
            $this->update(['client_type' => $type]);
            return true;
        }
        return false;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::uuid();
            }
        });
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'client_id', 'id');
    }

    public function address()
    {
        return $this->hasMany(ClientAddress::class, 'client_id', 'id');
    }

    public function payments()
    {
        return $this->hasManyThrough(
            Payment::class,
            Order::class,
            'client_id', // Foreign key on orders table...
            'order_id',  // Foreign key on payments table...
            'id',        // Local key on clients table...
            'id'         // Local key on orders table...
        );
    }
}