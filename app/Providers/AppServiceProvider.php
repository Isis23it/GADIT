<?php

namespace App\Providers;

use App\Models\Gasto;
use App\Models\Usuario;
use App\Policies\GastoPolicy;
use App\Policies\UsuarioPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // RF-01 y RNF-01: sin consultas N+1; fuera de producción una carga perezosa lanza excepción.
        Model::preventLazyLoading(! $this->app->isProduction());

        // SEG-08: morph map estricto para adjuntos; lanza excepción si se usa una clave no registrada.
        // shortcut: solo modelos implementados; cada PR que añada entidad debe registrar su clave aquí.
        Relation::enforceMorphMap([
            'gasto' => Gasto::class,
        ]);

        Gate::policy(Usuario::class, UsuarioPolicy::class);
        Gate::policy(Gasto::class, GastoPolicy::class);
    }
}
