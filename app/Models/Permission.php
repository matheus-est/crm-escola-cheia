<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\PermissionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[ObservedBy(PermissionObserver::class)]
class Permission extends Model implements Auditable
{
    use AuditableTrait, HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'module_id',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    public function scopeByModule(Builder $query, int $moduleId): Builder
    {
        return $query->where('module_id', $moduleId);
    }

    public function getModuleLabelAttribute(): string
    {
        return $this->module?->name ?? 'N/A';
    }
}
