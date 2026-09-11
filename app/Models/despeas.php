<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class despeas extends Model
{
    protected $table="despeas";
protected $fillable = ["compra_id","tipo","Descricao",'valor'];
}
