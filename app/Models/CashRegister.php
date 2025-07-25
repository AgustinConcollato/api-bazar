<?php

namespace App\Models;

use App\Models\Movement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CashRegister extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['name', 'description', 'balance'];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->id = $model->id  ?: (string) Str::uuid();
        });
    }

    public function movements()
    {
        return $this->hasMany(Movement::class)->orderBy('created_at','desc');
    }
}
