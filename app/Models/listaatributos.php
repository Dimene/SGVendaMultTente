<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class listaatributos extends Model
{
    protected $table="listaatributos";
     public $timestamps = false;
    protected $fillable = ['Descricao'];

}
