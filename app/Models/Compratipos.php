<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Compratipos extends Model
{
    protected $table = 'compratipos';

    public $timestamps = false;

    protected $fillable = [
        'Descricao',
    ];
}
