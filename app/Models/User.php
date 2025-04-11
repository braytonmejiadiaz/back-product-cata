<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Sale\Cart;
use App\Models\Sale\UserAddres;
use App\Models\Product\Product;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\Plan;


class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'surname',
        'type_user',
        'avatar',
        'phone',
        'email',
        "uniqd",
        "code_verified",
        'password',
        "email_verified_at",
        "address",
        "description",
        "fb",
        "ins",
        "tikTok",
        "youtube",
        "sexo",
        "slug",
        "store_name",
        "popup",
        "menu_color",
        "button_color",
        "mision",
        "vision",
        "plan_id",
        "button_radio",
        "mercadopago_subscription_id"
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function carts(){
        return $this->hasMany(Cart::class,"user_id");
    }

    public function address(){
        return $this->hasMany(UserAddres::class,"user_id");
    }
    public function products() {
        return $this->hasMany(Product::class);
    }
    public function plan()
{
    return $this->belongsTo(Plan::class);
}


}
