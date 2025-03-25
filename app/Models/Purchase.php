<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Product\Product;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_price',
        'nombre',
        'direccion',
        'ciudad',
        'telefono',
        'metodo_pago',
        'comentario',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'purchase_items')
                    ->withPivot('quantity', 'unit_price', 'total_price')
                    ->withTimestamps();
    }
}
