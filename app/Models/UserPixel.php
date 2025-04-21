<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class UserPixel extends Model
{
    use HasFactory;

    /**
     * Campos asignables masivamente
     */
    protected $fillable = [
        'user_id',
        'platform',
        'pixel_id',
        'is_active',
        'tienda_slug'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }


}
