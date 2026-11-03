<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROL_ADMIN = 'admin';

    public const ROL_CLIENTE = 'cliente';

    public const ROL_RECEPCIONISTA = 'recepcionista';
    public const CELULAR_REGEX = '/^(?:\+591)?[67][0-9]{7}$/';

    public const ROLES = [
        self::ROL_CLIENTE => 'Cliente',
        self::ROL_RECEPCIONISTA => 'Recepcionista',
        self::ROL_ADMIN => 'Administrador',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'telefono',
        'rol',
        'estado',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
            'estado' => 'boolean',
        ];
    }

    /**
     * Determina si el usuario tiene rol de administrador.
     */
    public function esAdministrador(): bool
    {
        return $this->rol === self::ROL_ADMIN;
    }

    /**
     * Determina si el usuario tiene rol de recepcionista.
     */
    public function esRecepcionista(): bool
    {
        return $this->rol === self::ROL_RECEPCIONISTA;
    }

    /**
     * Determina si el usuario puede realizar tareas operativas de recepción.
     */
    public function esRecepcionistaOAdministrador(): bool
    {
        return $this->esRecepcionista() || $this->esAdministrador();
    }

    /**
     * Determina si el usuario está activo en el sistema.
     */
    public function estaActivo(): bool
    {
        return (bool) $this->estado;
    }

    /**
     * Reservas realizadas por el usuario.
     */
    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }

    /**
     * Cuenta de Google vinculada para iniciar sesión.
     */
    public function googleAccount(): HasOne
    {
        return $this->hasOne(SocialAccount::class)->where('provider', SocialAccount::PROVIDER_GOOGLE);
    }

    public function tienePasswordLocal(): bool
    {
        return filled($this->password);
    }

    public function tieneCelularValido(): bool
    {
        return is_string($this->telefono) && preg_match(self::CELULAR_REGEX, $this->telefono) === 1;
    }

    /**
     * Conversaciones mantenidas con el chatbot.
     */
    public function conversaciones(): HasMany
    {
        return $this->hasMany(Conversacion::class);
    }
}
