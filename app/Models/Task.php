<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Concerns\BelongsToTenant;
use App\Observers\TaskObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[ObservedBy(TaskObserver::class)]
class Task extends Model implements Auditable
{
    use AuditableTrait, BelongsToTenant, HasFactory;

    protected $fillable = [
        'uuid',
        'school_id',
        'opportunity_id',
        'assigned_user_id',
        'outcome_id',
        'type',
        'status',
        'notes',
        'refusal_category',
        'refusal_detail',
        'scheduled_at',
        'due_at',
        'completed_at',
        'cancelled_at',
        'notified_overdue_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => TaskType::class,
            'status' => TaskStatus::class,
            'scheduled_at' => 'datetime',
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'notified_overdue_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function outcome(): BelongsTo
    {
        return $this->belongsTo(Outcome::class);
    }
}
