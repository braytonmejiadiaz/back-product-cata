<?php

namespace App\Models\Discount;

use Carbon\Carbon;
use App\Models\Product\Brand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DiscountBrand extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        "discount_id",
        "brand_id",
    ];

    public function setCreatedAtAttribute($value){
        date_default_timezone_set("America/Lima");
        $this->attributes["created_at"] = Carbon::now();
    }
    public function setUpdatedtAttribute($value){
        date_default_timezone_set("America/Lima");
        $this->attributes["updated_at"] = Carbon::now();
    }

    public function brand(){
        return $this->belongsTo(Brand::class);
    }

    public function discount(){
        return $this->belongsTo(Discount::class);
    }
}
