<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        if (! $this->configured()) {
            return redirect()->route('login')->withErrors([
                'google' => 'El acceso con Google todavía no está configurado.',
            ]);
        }

        $request->session()->put('google_oauth_intent', 'login');

        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function redirectForLink(Request $request): RedirectResponse
    {
        if (! $this->configured()) {
            return back()->withErrors([
                'google' => 'El acceso con Google todavía no está configurado.',
            ]);
        }

        $request->session()->put('google_oauth_intent', 'link');

        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $intent = $request->session()->pull('google_oauth_intent', 'login');

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            report($exception);

            return $this->failureRedirect($intent, 'No se pudo completar la autenticación con Google. Inténtalo nuevamente.');
        }

        if (! $this->hasVerifiedEmail($googleUser)) {
            return $this->failureRedirect($intent, 'Google no proporcionó un correo electrónico verificado.');
        }

        return $intent === 'link'
            ? $this->linkAuthenticatedUser($request, $googleUser)
            : $this->loginOrRegister($request, $googleUser);
    }

    public function unlink(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->tienePasswordLocal()) {
            return back()->withErrors([
                'google' => 'Define primero una contraseña local antes de desvincular Google.',
            ]);
        }

        $user->googleAccount()->delete();

        return back()->with('status', 'Cuenta de Google desvinculada correctamente.');
    }

    private function loginOrRegister(Request $request, SocialiteUser $googleUser): RedirectResponse
    {
        $account = SocialAccount::query()
            ->with('user')
            ->where('provider', SocialAccount::PROVIDER_GOOGLE)
            ->where('provider_user_id', (string) $googleUser->getId())
            ->first();

        if ($account) {
            if (! $account->user->estaActivo()) {
                return redirect()->route('login')->withErrors([
                    'google' => 'Esta cuenta se encuentra desactivada.',
                ]);
            }

            $account->update([
                'provider_email' => (string) $googleUser->getEmail(),
                'avatar_url' => $googleUser->getAvatar(),
            ]);

            return $this->finishLogin($request, $account->user);
        }

        $existingUser = User::query()
            ->where('email', (string) $googleUser->getEmail())
            ->first();

        if ($existingUser) {
            return redirect()->route('login')->withErrors([
                'google' => 'Ya existe una cuenta con ese correo. Inicia sesión con tu contraseña y vincula Google desde tu perfil.',
            ]);
        }

        $user = DB::transaction(function () use ($googleUser) {
            $user = User::create([
                'name' => $googleUser->getName() ?: 'Cliente Google',
                'email' => (string) $googleUser->getEmail(),
                'password' => null,
                'rol' => User::ROL_CLIENTE,
                'estado' => true,
            ]);

            // Este atributo no forma parte de $fillable porque su modificacion
            // debe quedar reservada a flujos de verificacion confiables.
            $user->forceFill(['email_verified_at' => now()])->save();

            $user->googleAccount()->create([
                'provider' => SocialAccount::PROVIDER_GOOGLE,
                'provider_user_id' => (string) $googleUser->getId(),
                'provider_email' => (string) $googleUser->getEmail(),
                'avatar_url' => $googleUser->getAvatar(),
            ]);

            return $user;
        });

        return $this->finishLogin($request, $user);
    }

    private function linkAuthenticatedUser(Request $request, SocialiteUser $googleUser): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login')->withErrors([
                'google' => 'Tu sesión venció. Inicia sesión para vincular Google.',
            ]);
        }

        $owner = SocialAccount::query()
            ->where('provider', SocialAccount::PROVIDER_GOOGLE)
            ->where('provider_user_id', (string) $googleUser->getId())
            ->where('user_id', '!=', $user->id)
            ->exists();

        if ($owner) {
            return redirect()->route('perfil.edit')->withErrors([
                'google' => 'Esa cuenta de Google ya está vinculada con otro usuario.',
            ]);
        }

        $user->googleAccount()->updateOrCreate([], [
            'provider' => SocialAccount::PROVIDER_GOOGLE,
            'provider_user_id' => (string) $googleUser->getId(),
            'provider_email' => (string) $googleUser->getEmail(),
            'avatar_url' => $googleUser->getAvatar(),
        ]);

        return redirect()->route('perfil.edit')->with('status', 'Cuenta de Google vinculada correctamente.');
    }

    private function finishLogin(Request $request, User $user): RedirectResponse
    {
        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->forget('reserva_pendiente');

        return redirect()->intended(match ($user->rol) {
            User::ROL_ADMIN => route('admin.dashboard'),
            User::ROL_RECEPCIONISTA => route('recepcionista.reservas.hoy'),
            default => route('home'),
        });
    }

    private function hasVerifiedEmail(SocialiteUser $googleUser): bool
    {
        $raw = is_array($googleUser->user ?? null) ? $googleUser->user : [];
        $verified = $raw['email_verified'] ?? $raw['verified_email'] ?? false;

        return filled($googleUser->getId())
            && filled($googleUser->getEmail())
            && filter_var($verified, FILTER_VALIDATE_BOOL);
    }

    private function failureRedirect(string $intent, string $message): RedirectResponse
    {
        $route = $intent === 'link' ? 'perfil.edit' : 'login';

        return redirect()->route($route)->withErrors(['google' => $message]);
    }

    private function configured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }
}
