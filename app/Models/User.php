<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[ObservedBy(UserObserver::class)]
class User extends Authenticatable implements Auditable
{
    use AuditableTrait, HasFactory, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'password',
        'role_id',
        'password_changed_at',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected $auditExclude = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_changed_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Determines whether the user has cross-tenant access.
     * Cross-tenant roles can operate across multiple schools without a school_user pivot entry.
     */
    public function isCrossTenant(): bool
    {
        return in_array($this->role?->name, ['Master', 'Admin', 'Operacao'], strict: true);
    }

    /**
     * Determines whether the user holds the Master role.
     */
    public function isMaster(): bool
    {
        return $this->role?->name === 'Master';
    }

    /**
     * Returns the currently active School for this user, resolved from the service container.
     *
     * The tenant school_id is bound to the container by SetActiveTenant middleware.
     *
     * TODO: model School criado na Etapa 1.x — retorna null até que o model exista.
     *
     * @return School|null
     */
    public function currentSchool(): ?object
    {
        // School model does not exist until Etapa 1.x — guard to avoid fatal error.
        if (! class_exists(School::class)) { // @phpstan-ignore-line
            return null;
        }

        if (app()->bound('tenant.school_id')) {
            return School::find(app('tenant.school_id')); // @phpstan-ignore-line
        }

        return null;
    }
}
