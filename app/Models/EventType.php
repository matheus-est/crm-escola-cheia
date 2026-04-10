<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\EventTypeScope;
use App\Observers\EventTypeObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[ObservedBy(EventTypeObserver::class)]
class EventType extends Model implements Auditable
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
     * Boot the model and apply EventTypeScope as a global scope.
     *
     * Shows both system records (school_id = null, is_system = true)
     * and tenant-specific records simultaneously.
     * BelongsToTenant is not used here — scope handles visibility.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new EventTypeScope);
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
     * Return a query builder instance without the EventTypeScope applied.
     * Use for cross-tenant or admin queries (e.g. seeder).
     */
    public static function withoutEventTypeScope(): Builder
    {
        return static::withoutGlobalScope(EventTypeScope::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
