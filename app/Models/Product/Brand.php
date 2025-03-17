<?php

namespace App\Models\Product;

use Carbon\Carbon;
use App\Models\Discount\DiscountBrand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class Brand extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        "name",
        "state",
        "imagen",
        "user_id",
    ];

    public function setCreatedAtAttribute($value){
        date_default_timezone_set("America/Lima");
        $this->attributes["created_at"] = Carbon::now();
    }
    public function setUpdatedtAttribute($value){
        date_default_timezone_set("America/Lima");
        $this->attributes["updated_at"] = Carbon::now();
    }

    public function products(){
        return $this->hasMany(Product::class);
    }

    public function discount_brands() {
        return $this->hasMany(DiscountBrand::class,"brand_id");
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
