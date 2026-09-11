<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaAtributo extends Model
{
    protected $table = "categoria_atributos";
    public $timestamps = false;

protected $fillable = ["categoria_id", "atributo_id"];



    // Relacionamentos
    public function categoria()
    {
        return $this->belongsTo(Categoria::class,'id','categoria_id');
    }

    public function atributo()
    {
        return $this->belongsTo(listaatributos::class,'id','atributo_id');
    }
}
