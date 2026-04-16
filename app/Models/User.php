<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SchoolStatus;
use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'school_current_id',
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

    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'school_user', 'user_id', 'school_id')
            ->withPivot('is_active');
    }

    public function isCrossTenant(): bool
    {
        return in_array($this->role?->name, ['Master', 'Admin', 'Operacao'], strict: true);
    }

    public function isMaster(): bool
    {
        return $this->role?->name === 'Master';
    }

    public function isComercial(): bool
    {
        return $this->role?->name === 'Comercial';
    }

    public function currentSchool(): ?School
    {
        if (! class_exists(School::class)) {
            return null;
        }

        if (app()->bound('tenant.school_id')) {
            return School::find(app('tenant.school_id'));
        }

        $uuid = session('active_school_uuid');
        if ($uuid === null) {
            return null;
        }

        $school = School::where('uuid', $uuid)->first();
        if ($school === null) {
            return null;
        }

        if ($this->isCrossTenant()) {
            return $school->status === SchoolStatus::Active ? $school : null;
        }

        return $this->schools()
            ->where('schools.id', $school->id)
            ->wherePivot('is_active', true)
            ->first();
    }
}
