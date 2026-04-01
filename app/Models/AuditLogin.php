<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class AuditLogin extends Model implements Auditable
{
    use AuditableTrait;

    /**
     * Disable audit events to prevent infinite loop (AuditLogin auditing itself).
     *
     * @var array<int, string>
     */
    protected array $auditEvents = [];

    protected $table = 'audit_logins';

    protected $fillable = [
        'user_id',
        'ip_address',
        'login_type',
        'browser',
        'browser_version',
        'os',
        'device',
        'session_id',
        'logged_in_at',
        'logged_out_at',
        'success',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'logged_in_at' => 'datetime',
            'logged_out_at' => 'datetime',
            'success' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
