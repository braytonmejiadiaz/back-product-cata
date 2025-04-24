<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = ['name', 'description', 'is_active'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_payment_methods')
                   ->withPivot('is_default')
                   ->withTimestamps();
    }
}
