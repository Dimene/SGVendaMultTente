<?php

namespace App\Providers;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Inertia\Inertia;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        /*
        |--------------------------------------------------------------------------
        | Empresa global para o Inertia
        |--------------------------------------------------------------------------
        */
        Inertia::share(
            'empresa',
            fn () => Empresa::query()->find(
                1,
                ['id', 'nome', 'nome_fantasia', 'logo']
            )
        );

        /*
        |--------------------------------------------------------------------------
        | ACL / Permissões
        |--------------------------------------------------------------------------
        |
        | Permite usar:
        |
        | auth()->user()->can('compra-show')
        |
        */

        Gate::before(function (User $user, string $ability) {

            /*
            |--------------------------------------------------------------------------
            | Administrador tem acesso total
            |--------------------------------------------------------------------------
            */
            if ($user->roles()->where('name', 'admin')->exists()) {
                return true;
            }

            /*
            |--------------------------------------------------------------------------
            | Verifica a permissão pelo nome
            |--------------------------------------------------------------------------
            */
            if ($user->temPermissao($ability)) {
                return true;
            }

            /*
            |--------------------------------------------------------------------------
            | Deixa o Laravel continuar a verificação normal
            |--------------------------------------------------------------------------
            */
            return null;
        });
    }
}