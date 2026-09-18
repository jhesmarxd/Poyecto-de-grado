<?php

namespace App\Providers;

use App\Contracts\GoogleCalendarGateway;
use App\Models\Ajuste;
use App\Models\User;
use App\Policies\UsuarioPolicy;
use App\Services\GoogleCalendarService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(GoogleCalendarGateway::class, GoogleCalendarService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UsuarioPolicy::class);

        // Artisan debe poder arrancar aunque MySQL este temporalmente fuera de
        // servicio. La consulta del tema se difiere hasta renderizar una vista.
        View::share('colores', Ajuste::coloresPorDefecto());
        View::composer('*', function ($view): void {
            static $colores;

            if ($colores === null) {
                $colores = Ajuste::coloresPorDefecto();

                try {
                    if (Schema::hasTable('ajustes')) {
                        $colores = Ajuste::colores();
                    }
                } catch (Throwable) {
                    // Mantiene una paleta valida cuando la BD no esta disponible.
                }
            }

            $view->with('colores', $colores);
        });

        RateLimiter::for('chatbot', function (Request $request): Limit {
            $usuario = $request->user();
            $maximo = $usuario
                ? (int) config('chatbot.rate_limit.authenticated', 30)
                : (int) config('chatbot.rate_limit.guest', 10);
            $identificador = $usuario
                ? 'user:'.$usuario->getAuthIdentifier()
                : 'guest:'.hash('sha256', (string) $request->ip());

            return Limit::perMinute(max(1, $maximo))->by($identificador);
        });
    }
}
