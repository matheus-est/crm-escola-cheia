<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\ModuleActionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[ObservedBy(ModuleActionObserver::class)]
class ModuleAction extends Model implements Auditable
{
    use AuditableTrait, HasFactory;

    protected $fillable = [
        'uuid',
        'module_id',
        'name',
        'label',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
