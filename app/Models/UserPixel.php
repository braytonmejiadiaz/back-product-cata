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
        'user_id',     // Asegúrate que existe en tu tabla
        'platform',    // Campo que menciona el error
        'pixel_id',    // Campo necesario según tu lógica
        'is_active'    // Si existe en tu estructura
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }


}
