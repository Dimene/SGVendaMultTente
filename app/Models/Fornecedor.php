<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fornecedor extends Model
{

protected $table="fornecedores";
    protected $fillable = [
        'empresa_id',
        'nome',
        'documento',
        'email',
        'endereco',
        'cidade',
        'provincia',
        'observacoes',
        'ativo'
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function distrito()
    {
        return $this->hasOne(distrito::class,"id","cidade");
    }

    public function provincia()
    {
        return $this->hasOne(provincia::class,"id","provincia");
    }


    public function contactos()
    {
        return $this->hasMany(contacto::class, 'proprietario_id', 'id')
                ->where('tipoproprietario', 'F');
    }


}
