<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\LeadSourceScope;
use App\Observers\LeadSourceObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[ObservedBy(LeadSourceObserver::class)]
class LeadSource extends Model implements Auditable
{
    use AuditableTrait, HasFactory;

    protected $fillable = [
        'uuid',
        'school_id',
        'name',
        'is_system',
        'is_active',
    ];

    /**
     * Boot the model and apply the LeadSourceScope as a global scope.
     *
     * This custom scope shows both system records (school_id = null, is_system = true)
     * and tenant-specific records (school_id = current tenant) simultaneously,
     * which is why BelongsToTenant is not used here.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new LeadSourceScope);
    }

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Return a query builder instance without the LeadSourceScope applied.
     * Use for cross-tenant or admin queries.
     */
    public static function withoutLeadSourceScope(): Builder
    {
        return static::withoutGlobalScope(LeadSourceScope::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
