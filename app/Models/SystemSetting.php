<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * App\Models\SystemSetting
 *
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property string $group
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class SystemSetting extends Model implements Auditable
{
    use AuditableTrait;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [];
    }

    /**
     * The attributes that should be audited.
     */
    protected $auditExclude = [];

    /**
     * The audit events to be logged.
     */
    protected $auditEvents = ['created', 'updated', 'deleted'];

    /**
     * The audit friendly name.
     */
    protected $auditFriendlyName = 'System Setting';
}
