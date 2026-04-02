<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SchoolStatus;
use App\Observers\SchoolObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[ObservedBy(SchoolObserver::class)]
class School extends Model implements Auditable
{
    use AuditableTrait, HasFactory;

    public $incrementing = false;

    public $keyType = 'string';

    protected $fillable = [
        'cnpj',
        'razao_social',
        'slug',
        'logo_path',
        'address_json',
        'status',
        'observations',
        'unassigned_lead_alert_days',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'status' => SchoolStatus::class,
        ];
    }

    public function units(): HasMany
    {
        return $this->hasMany(SchoolUnit::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'school_user')
            ->withPivot('is_active')
            ->withTimestamps();
    }
}
