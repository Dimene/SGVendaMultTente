<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $fillable = [
        'nome',
        'nome_fantasia',
        'documento',
        'email',
        'telefone',
        'celular',
        'endereco',
        'cidade',
        'provincia',
        'pais',
        'site',
        'logo',
        'ativo'
    ];

    public function usuarios()
    {
        return $this->hasMany(Usuario::class);
    }

    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }

    public function fornecedores()
    {
        return $this->hasMany(Fornecedor::class);
    }

    public function produtos()
    {
        return $this->hasMany(Produto::class);
    }
}
