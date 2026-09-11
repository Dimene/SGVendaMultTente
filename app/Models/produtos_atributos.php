<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class produtos_atributos extends Model
{
     protected $table = 'produtos_atributos';

    public $timestamps = false;

    protected $fillable = [
        'produto_item_id',
        'outrosAtributos',
        'fotos',

    ];

    public function nomeatributo(){

    return  $this->hasOne(listaatributos::class,'id','atributo_id');
    }

}
