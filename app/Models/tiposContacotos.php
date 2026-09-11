<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class tiposContacotos extends Model
{
    //
     protected $table="tiposcontactos";
public $timestamps = false;
    protected $fillable = ["id","Descricao"];

}
