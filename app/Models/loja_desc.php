<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class loja_desc extends Model
{
protected $table="loja_desc";
protected $fillable = ["Desc","endereco_id"];
}
