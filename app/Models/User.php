<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;


    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            
        ];

       
    }

     public function roles() {
        return $this->belongsToMany(role::class,'role_user', 'user_id', 'role_id');
    }

    

public function temPermissao(string $permissao): bool
{
    // Administrador tem acesso total
    if ($this->roles()->where('name', 'Admin')->exists()) {
        return true;
    }

    // Outros usuários precisam ter a permissão
    return $this->roles()
        ->whereHas('permissions', function ($query) use ($permissao) {
            $query->where('name', $permissao);
        })
        ->exists();
}
}
