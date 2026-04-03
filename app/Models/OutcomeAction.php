<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\OutcomeActionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(OutcomeActionObserver::class)]
class OutcomeAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'outcome_id',
        'action_type',
        'payload',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function outcome(): BelongsTo
    {
        return $this->belongsTo(Outcome::class);
    }
}
