<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Observers\EventObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[ObservedBy(EventObserver::class)]
class Event extends Model implements Auditable
{
    use AuditableTrait, BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'title',
        'event_type_id',
        'has_no_date',
        'grade_id',
        'event_date',
        'location',
        'max_capacity',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'datetime',
            'has_no_date' => 'boolean',
            'max_capacity' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function opportunities(): BelongsToMany
    {
        return $this->belongsToMany(Opportunity::class, 'event_opportunity');
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'event_room');
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
