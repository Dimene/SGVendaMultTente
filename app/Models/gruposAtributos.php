<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class gruposAtributos extends Model
{
    //
    //
     protected $table = 'grupo_atributos';

    public $timestamps = false;

    protected $fillable = [

        'grupo_id',
        'atributo_id',
        'empresa_id',
    ];
}
