<?php

namespace App\Providers;

use App\Contracts\GoogleCalendarGateway;
use App\Models\User;
use App\Policies\UsuarioPolicy;
use App\Services\GoogleCalendarService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
