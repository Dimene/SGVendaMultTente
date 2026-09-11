<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $fillable = [
        'empresa_id',
        'nome',
        'email',
        'password',
        'telefone',
        'cargo',
        'avatar',
        'ativo'
    ];

    protected $hidden = [
        'password'
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
