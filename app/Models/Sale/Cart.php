<?php

namespace App\Models\Sale;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product\ProductVariation;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cart extends Model
{
    use HasFactory;
    protected $fillable = [
        "user_id",
        "product_id",
        "type_discount",
        "discount",
        "type_campaing",
        "code_cupon",
        "code_discount",
        "product_variation_id",
        "quantity",
        "price_unit",
        "subtotal",
        "total",
        "currency",
        "updated_at"
    ];

    public function setCreatedAtAttribute($value){
        date_default_timezone_set("America/Lima");
        $this->attributes["created_at"] = Carbon::now();
    }
    public function setUpdatedtAttribute($value){
        date_default_timezone_set("America/Lima");
        $this->attributes["updated_at"] = Carbon::now();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function product_variation()
    {
        return $this->belongsTo(ProductVariation::class);
    }
}
