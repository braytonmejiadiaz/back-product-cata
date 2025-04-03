<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class Slider extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        "title",
        // "subtitle",
        "label",
        "imagen",
        // "link",
        "state",
        // "color",
        "type_slider",
        "price_original",
        "price_campaing",
        "user_id"

    ];

    public function setCreatedAtAttribute($value){
        date_default_timezone_set("America/Lima");
        $this->attributes["created_at"] = Carbon::now();
    }
    public function setUpdatedtAttribute($value){
        date_default_timezone_set("America/Lima");
        $this->attributes["updated_at"] = Carbon::now();
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
