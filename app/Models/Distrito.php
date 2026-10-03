<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Distrito extends Model
{
    protected $table = 'distritos';

    public $timestamps = false;

    protected $fillable = [
        'Nome',
        'provincia_id',
    ];

}
