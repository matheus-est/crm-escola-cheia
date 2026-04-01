<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\TermVersionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[ObservedBy(TermVersionObserver::class)]
class TermVersion extends Model implements Auditable
{
    use AuditableTrait, HasFactory;

    protected $fillable = [
        'uuid',
        'version',
        'title',
        'content',
        'is_active',
        'effective_at',
        'created_by',
    ];

    protected $auditExclude = ['content'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'effective_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function acceptances(): HasMany
    {
        return $this->hasMany(UserTermAcceptance::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->orderBy('effective_at', 'desc')
            ->orderBy('created_at', 'desc');
    }
}
