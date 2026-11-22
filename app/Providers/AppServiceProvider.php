<?php

namespace App\Providers;

use App\Models\Usuario;
use App\Policies\UsuarioPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // RF-01 y RNF-01: sin consultas N+1; fuera de producción una carga perezosa lanza excepción.
        Model::preventLazyLoading(! $this->app->isProduction());

        Gate::policy(Usuario::class, UsuarioPolicy::class);
    }
}
