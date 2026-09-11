<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $fillable = [
        'empresa_id',
        'nome',
        'documento',
        'email',
        'telefone',
        'celular',
        'endereco',
        'cidade',
        'provincia',
        'limite_credito',
        'status'
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
