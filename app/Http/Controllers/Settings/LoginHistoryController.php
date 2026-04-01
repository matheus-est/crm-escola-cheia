<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\Acl\AuditLoginService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoginHistoryController extends Controller
{
    public function __construct(
        protected readonly AuditLoginService $auditLoginService,
    ) {}

    public function show(Request $request): Response
    {
        $user = $request->user();

        if ($request->isMethod('POST')) {
            $filters = [
                'per_page' => $request->input('per_page', 10),
            ];
            session(['login_history_filters' => $filters]);
        }

        $filters = session('login_history_filters', ['per_page' => 10]);
        $perPage = $filters['per_page'] ?? 10;

        $loginAudits = $this->auditLoginService->getByUser($user->id, (int) $perPage);

        return Inertia::render('settings/LoginHistory', [
            'user' => [
                'uuid' => $user->uuid,
                'name' => $user->name,
            ],
            'loginAudits' => $loginAudits,
            'filters' => $filters,
        ]);
    }
}
