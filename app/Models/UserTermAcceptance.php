<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class UserTermAcceptance extends Model implements Auditable
{
    use AuditableTrait;

    protected $table = 'user_term_acceptances';
    protected $auditEvents = ['created'];
    
    protected $fillable = [
        'user_id',
        'term_version_id',
        'accepted_at',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function termVersion(): BelongsTo
    {
        return $this->belongsTo(TermVersion::class, 'term_version_id');
    }
}
