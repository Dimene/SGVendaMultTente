<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class permissoes extends Model
{
    protected $table = 'permissions';

    protected $fillable = [
        'name',
        'label',
        'group',
    ];

    public function roles()
    {
        return $this->belongsToMany(
            role::class,
            'permission_role',
            'permission_id',
            'role_id'
        );
    }
}