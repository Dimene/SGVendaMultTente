<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Override;

class Categoria extends Model
{
    protected $fillable = [
        'nome',
        'tipo',
        'grupo_id',
        'ativo',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function pai()
    {
        return $this->belongsTo(Categoria::class,'pai_id');
    }

    public function filhos()
    {
        return $this->hasMany(Categoria::class,'pai_id');
    }

    public function produtos()
    {
        return $this->hasMany(Produto::class);
    } public function grupo()
    {
        return $this->hasOne(gruposItem::class,'id','grupo_id');
    }


    public function atributos()
    {
        return $this->belongsToMany(listaatributos::class, "categoria_atributos","categoria_id","atributo_id","id","id");

    }

}
