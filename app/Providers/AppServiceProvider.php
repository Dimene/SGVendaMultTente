<?php

namespace App\Providers;

use App\Models\Empresa;
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

        Inertia::share('empresa', fn () => Empresa::query()->find(1, ['id', 'nome', 'nome_fantasia', 'logo']));
    }
}
