<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserShippingOption extends Model
{
    protected $table = 'user_shipping_options';

    protected $fillable = [
        'user_id',
        'is_free',       // boolean: true para envío gratis
        'shipping_rate'  // decimal: valor del envío cuando no es gratis
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
