<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class contacto extends Model
{
    protected $table="contactos";
public $timestamps = false;
    protected $fillable = ["tipo_id","valor","proprietario_id","tipoproprietario"];



public function tipocontacto(){
    return $this->hasOne(tiposContacotos::class,"id","tipo_id");
}
}
