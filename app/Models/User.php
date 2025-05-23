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
use App\Models\UserPixel;
use App\Models\PaymentMethod;
use App\Models\CustomDomain;
use App\Models\UerShippingOption;
use Illuminate\Database\Eloquent\Relations\HasMany;





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
        "selected_font",
        "bg_color",
        "mercadopago_subscription_id",
        'currency',
        'currency_symbol',
        'country_code'
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

public function customDomain()
{
    return $this->hasOne(\App\Models\CustomDomain::class);
}

public function pixels(): HasMany
{
    return $this->hasMany(UserPixel::class);
}

public function paymentMethods()
    {
        return $this->belongsToMany(PaymentMethod::class, 'user_payment_methods')
                   ->withPivot('is_default')
                   ->withTimestamps();
    }

    protected $attributes = [
        'selected_font' => 'Roboto'
    ];

    public function getFontFamilyAttribute()
    {
        return config("fonts.available.{$this->selected_font}.family")
               ?? config("fonts.available.".config('fonts.default').".family");
    }
    // En app/Models/User.php
public function shippingOption()
{
    return $this->hasOne(UserShippingOption::class);
}

}
