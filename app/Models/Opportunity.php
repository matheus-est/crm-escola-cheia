<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OpportunityStatus;
use App\Enums\RegistrationType;
use App\Models\Concerns\BelongsToTenant;
use App\Observers\OpportunityObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[ObservedBy(OpportunityObserver::class)]
class Opportunity extends Model implements Auditable
{
    use AuditableTrait, BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'school_id',
        'student_id',
        'guardian_id',
        'grade_id',
        'school_year_id',
        'lead_source_id',
        'responsible_user_id',
        'segment_id',
        'status',
        'observations',
        'history',
        'indications',
        'registration_type',
    ];

    protected function casts(): array
    {
        return [
            'status' => OpportunityStatus::class,
            'registration_type' => RegistrationType::class,
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

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function leadSource(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class);
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function segment(): BelongsTo
    {
        return $this->belongsTo(Segment::class);
    }
}
