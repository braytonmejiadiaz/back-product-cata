<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aviso extends Model
{
    use HasFactory;

    protected $fillable = ['contenido', 'estilos', 'user_id'];

    protected $casts = [
        'estilos' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
