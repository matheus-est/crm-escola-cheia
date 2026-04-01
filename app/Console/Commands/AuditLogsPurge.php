<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AuditLogin;
use App\Services\SettingService;
use Illuminate\Console\Command;

class AuditLogsPurge extends Command
{
    protected $signature = 'audit-logs:purge';

    protected $description = 'Remove audit logs older than configured days';

    /**
     * Constructor.
     */
    public function __construct(
        protected SettingService $settingService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $retentionDays = (int) $this->settingService->get('audit_retention_days', 180);

        $deleted = AuditLogin::where('logged_in_at', '<', now()->subDays($retentionDays))->delete();

        $this->info("Deleted {$deleted} old audit log entries.");

        return Command::SUCCESS;
    }
}
