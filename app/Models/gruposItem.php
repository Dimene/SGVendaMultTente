<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Categoria;
use App\Models\listaatributos;
class gruposItem extends Model
{
    //
     protected $table = 'grupositem';

    public $timestamps = false;

    protected $fillable = [

        'nome',
        'icone',
    ];


public function listaatributo(){

    return $this->belongsToMany(listaatributos::class,'grupo_atributos','grupo_id','atributo_id','id','id');
}

public  function  categoria(){

    return $this->hasMany(Categoria::class,'grupo_id','id');
}



}
