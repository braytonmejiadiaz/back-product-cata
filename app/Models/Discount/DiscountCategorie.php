<?php

namespace App\Models\Discount;

use Carbon\Carbon;
use App\Models\Product\Categorie;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DiscountCategorie extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        "discount_id",
        "categorie_id",
    ];

    public function setCreatedAtAttribute($value){
        date_default_timezone_set("America/Lima");
        $this->attributes["created_at"] = Carbon::now();
    }
    public function setUpdatedtAttribute($value){
        date_default_timezone_set("America/Lima");
        $this->attributes["updated_at"] = Carbon::now();
    }

    public function categorie(){
        return $this->belongsTo(Categorie::class);
    }
    
    public function discount(){
        return $this->belongsTo(Discount::class);
    }
}
