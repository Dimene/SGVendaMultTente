<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

class tabela_ivas extends Model
{
    protected $table="tabela_ivas";
    public $timestamps=false;
    protected $Fillable=["percentagem","status"];
}
