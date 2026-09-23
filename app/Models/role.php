<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class role extends Model
{
    protected $table = 'roles';

    protected $fillable = [
        'name',
        'label',
        'description',
    ];

    public function permissions()
    {
        return $this->belongsToMany(
            permissoes::class,
            'permission_role',
            'role_id',
            'permission_id'
        );
    }

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'role_user',
            'role_id',
            'user_id'
        );
    }
}