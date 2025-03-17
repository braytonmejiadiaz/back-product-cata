<?php

namespace App\Models\Cupone;

use Carbon\Carbon;
use App\Models\Product\Categorie;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CuponeCategorie extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        "cupone_id",
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
}
