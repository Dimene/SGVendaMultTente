<?php

namespace App\Services;

use App\Models\User;

class PermissionService
{
    /**
     * Verifica se o utilizador possui uma determinada permissão.
     *
     * O utilizador Admin possui acesso total.
     */
    public function temPermissao(?User $user, string $permissao): bool
    {
        if (!$user) {
            return false;
        }

        // Administrador possui todas as permissões
        $isAdmin = $user->roles?->contains(function ($role) {
            return $role->name === 'Admin';
        });

        if ($isAdmin) {
            return true;
        }

        // Obtém todas as permissões das roles do utilizador
        $permissoes = $user->roles
            ->flatMap(function ($role) {
                return $role->permissions ?? [];
            })
            ->pluck('name')
            ->unique()
            ->values();

        return $permissoes->contains($permissao);
    }

    /**
     * Alias semelhante ao can() do Laravel.
     */
    public function can(?User $user, string $permissao): bool
    {
        return $this->temPermissao($user, $permissao);
    }

    /**
     * Verifica se o utilizador possui pelo menos uma
     * das permissões informadas.
     */
    public function temAlgumaPermissao(
        ?User $user,
        array $permissoes
    ): bool {
        foreach ($permissoes as $permissao) {
            if ($this->temPermissao($user, $permissao)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica se o utilizador possui todas
     * as permissões informadas.
     */
    public function temTodasPermissoes(
        ?User $user,
        array $permissoes
    ): bool {
        foreach ($permissoes as $permissao) {
            if (!$this->temPermissao($user, $permissao)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Retorna todas as permissões do utilizador.
     */
    public function todasPermissoes(?User $user): array
    {
        if (!$user) {
            return [];
        }

        // Admin recebe acesso total.
        if ($user->roles?->contains('name', 'Admin')) {
            return ['*'];
        }

        return $user->roles
            ->flatMap(function ($role) {
                return $role->permissions ?? [];
            })
            ->pluck('name')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Verifica se é administrador.
     */
    public function isAdmin(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->roles?->contains('name', 'Admin') ?? false;
    }
}
