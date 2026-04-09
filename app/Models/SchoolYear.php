<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SchoolYearStatus;
use App\Models\Concerns\BelongsToTenant;
use App\Observers\SchoolYearObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[ObservedBy(SchoolYearObserver::class)]
class SchoolYear extends Model implements Auditable
{
    use AuditableTrait, BelongsToTenant, HasFactory;

    protected $fillable = [
        'uuid',
        'school_id',
        'name',
        'start',
        'end',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start' => 'date',
            'end' => 'date',
            'status' => SchoolYearStatus::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
