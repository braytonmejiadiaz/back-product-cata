<?php

namespace App\Models\Product;

use Carbon\Carbon;
use App\Models\Product\Propertie;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class Attribute extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        "name",
        "type_attribute",
        "state",
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

    public function properties(){
        return $this->hasMany(Propertie::class);
    }

    public function specifications(){
        return $this->hasMany(ProductSpecification::class);
    }

    public function variations(){
        return $this->hasMany(ProductVariation::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
