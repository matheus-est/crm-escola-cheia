<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Services\Acl\AuditLoginService;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;

class LogLogout
{
    public function __construct(
        protected readonly AuditLoginService $auditService,
        protected readonly Request $request,
    ) {}

    public function handle(Logout $event): void
    {
        $sessionId = $this->request->session()->getId();

        $this->auditService->markLogout($sessionId);
    }
}
