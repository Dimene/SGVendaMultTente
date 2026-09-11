<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Armazem extends Model
{
    protected $table="armazens";
     protected $fillable = ["Descricao"];


     function produtositem(){

    return $this->hasMany(produtoitems::class,'Armazem_id','id');

     }
}
