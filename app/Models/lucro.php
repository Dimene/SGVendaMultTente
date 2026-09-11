<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class lucro extends Model
{
     protected $table="lucros";
    public $timestamps=false;
    protected $Fillable=["percentagem","status"];
}
