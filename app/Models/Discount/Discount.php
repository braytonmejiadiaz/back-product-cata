<?php

namespace App\Models\Discount;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Discount extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        "code",
        "type_discount",
        "discount",
        "start_date",
        "end_date",
        "discount_type",
        "type_campaing",
        "state",
    ];

    public function setCreatedAtAttribute($value){
        date_default_timezone_set("America/Lima");
        $this->attributes["created_at"] = Carbon::now();
    }
    public function setUpdatedtAttribute($value){
        date_default_timezone_set("America/Lima");
        $this->attributes["updated_at"] = Carbon::now();
    }

    public function categories(){
        return $this->hasMany(DiscountCategorie::class);
    }

    public function products(){
        return $this->hasMany(DiscountProduct::class);
    }

    public function brands(){
        return $this->hasMany(DiscountBrand::class);
    }
}
