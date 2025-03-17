<?php

namespace App\Models\Cupone;

use Carbon\Carbon;
use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CuponeProduct extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        "cupone_id",
        "product_id",
    ];

    public function setCreatedAtAttribute($value){
        date_default_timezone_set("America/Lima");
        $this->attributes["created_at"] = Carbon::now();
    }
    public function setUpdatedtAttribute($value){
        date_default_timezone_set("America/Lima");
        $this->attributes["updated_at"] = Carbon::now();
    }
    public function product(){
        return $this->belongsTo(Product::class);
    }
}
