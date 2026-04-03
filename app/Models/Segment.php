<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\SegmentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[ObservedBy(SegmentObserver::class)]
class Segment extends Model implements Auditable
{
    use AuditableTrait, HasFactory;

    protected $fillable = [
        'uuid',
        'name',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return [];
    }

    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'school_segment');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }
}
