<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'description',
        'mercadopago_plan_id',
        'product_limit', // Añade este campo
        'is_free' // Añade este campo
    ];
}
