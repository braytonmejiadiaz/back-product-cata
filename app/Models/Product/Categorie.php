<?php

namespace App\Models\Product;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Models\Discount\DiscountCategorie;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class Categorie extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        "name",
        "icon",
        "imagen",
        "categorie_second_id",
        "categorie_third_id",
        "position",
        "type_categorie",
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

    public function categorie_second() {
        return $this->belongsTo(Categorie::class,"categorie_second_id");
    }

    public function categorie_third() {
        return $this->belongsTo(Categorie::class,"categorie_third_id");
    }

    public function categorie_seconds() {
        return $this->hasMany(Categorie::class,"categorie_second_id");
    }

    public function product_categorie_firsts(){
        return $this->hasMany(Product::class,"categorie_first_id");
    }

    public function product_categorie_secodns(){
        return $this->hasMany(Product::class,"categorie_second_id");
    }

    public function product_categorie_thirds(){
        return $this->hasMany(Product::class,"categorie_third_id");
    }

    public function discount_categories() {
        return $this->hasMany(DiscountCategorie::class,"categorie_id");
    }

        // En el modelo Categorie
    public function user() {
        return $this->belongsTo(User::class);
    }
}
