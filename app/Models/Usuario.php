<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{

protected $table="users";
    protected $fillable = [
        
        'name',
        'email',
        'password',
        
        'cargo',
        'avatar',
        'ativo'
    ];

    protected $hidden = [
        'password'
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
