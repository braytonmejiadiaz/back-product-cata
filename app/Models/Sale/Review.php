<?php

namespace App\Models\Sale;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Review extends Model
{
    use HasFactory;
    protected $fillable = [
        "product_id",
        "user_id",
        "sale_detail_id",
        "message",
        "rating"
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

    public function sale_detail()
    {
        return $this->belongsTo(SaleDetail::class);
    }
}
