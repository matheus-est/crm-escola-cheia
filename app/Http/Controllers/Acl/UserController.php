<?php

declare(strict_types=1);

namespace App\Http\Controllers\Acl;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Services\Acl\RoleService;
use App\Services\Acl\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function __construct(
        protected readonly UserService $userService,
        protected readonly RoleService $roleService,
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        $filters = $this->setFilters($request);

        $currentUser = $request->user();
        $paginator = $this->userService->filter($filters, $currentUser);

        $paginator->through(fn ($user): array => [
            'uuid' => $user->uuid,
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role ? [
                'id' => $user->role->id,
                'uuid' => $user->role->uuid,
                'name' => $user->role->name,
            ] : null,
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
            'deleted_at' => $user->deleted_at,
            // Auth::user() era redundante com $request->user() — removido
            'is_deletable' => ! $user->trashed()
                && $user->id !== $currentUser->id,
        ]);

        $roles = $this->roleService->getRestriction()
            ->map(fn ($r): array => ['id' => $r->id, 'uuid' => $r->uuid, 'name' => $r->name]);

        return Inertia::render('acl/Users/Index', [
            'users' => $paginator,
            'roles' => $roles,
            'filters' => $filters,
            'isCurrentUserMaster' => $currentUser?->role?->name === 'Master',
        ]);
    }

    public function setFilters(Request $request): array
    {
        $filters = [
            'name' => $request->input('name', ''),
            'email' => $request->input('email', ''),
            'role_id' => $request->input('role_id', ''),
            'status' => $request->input('status', 'active'),
            'sort_by' => $request->input('sort_by', 'name'),
            'sort_dir' => $request->input('sort_dir', 'asc'),
            'per_page' => $request->input('per_page', 10),
        ];

        session(['user_filters' => $filters]);

        return $filters;
    }

    public function clearFilters(): RedirectResponse
    {
        session()->forget('user_filters');

        return to_route('users.index');
    }

    public function create(): Response
    {
        $currentUser = request()->user();
        $roles = $this->roleService->getRestriction();

        return Inertia::render('acl/Users/Create', [
            'roles' => $roles->map(fn ($r): array => ['id' => $r->id, 'uuid' => $r->uuid, 'name' => $r->name]),
            'isCurrentUserMaster' => $currentUser?->role?->name === 'Master',
        ]);
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        $this->userService->create($request->validated());

        return to_route('users.index')
            ->with('success', 'Usuário criado com sucesso.');
    }

    public function edit(Request $request, string $userUuid): Response
    {
        $user = $this->userService->getByUuid($userUuid);
        $currentUser = $request->user();
        $roles = $this->roleService->getRestriction();
        $auditFilters = session('user_login_audit_filters', ['per_page' => 10]);
        $loginAudits = null;

        if ($currentUser->can('users_audit_login')) {
            $loginAudits = $this->userService->getLoginAudits(
                $user->id,
                (int) ($auditFilters['per_page'] ?? 10)
            );
        }

        return Inertia::render('acl/Users/Edit', [
            'user' => [
                'uuid' => $user->uuid,
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'role_uuid' => $user->role?->uuid,
                'role_name' => $user->role?->name,
            ],
            'roles' => $roles->map(fn ($r): array => ['id' => $r->id, 'uuid' => $r->uuid, 'name' => $r->name]),
            'isCurrentUserMaster' => $currentUser?->role?->name === 'Master',
            'loginAudits' => $loginAudits,
            'auditFilters' => $auditFilters,
        ]);
    }

    public function update(UserUpdateRequest $request, string $userUuid): RedirectResponse
    {
        $user = $this->userService->getByUuid($userUuid);
        $this->userService->update($user, $request->validated());

        return to_route('users.index')
            ->with('success', 'Usuário atualizado com sucesso.');
    }

    public function confirmDelete(Request $request, string $userUuid): RedirectResponse
    {
        $user = $this->userService->getByUuid($userUuid);

        try {
            $this->userService->confirmDelete(
                $user,
                $request->input('password'),
                $request->user()
            );

            return to_route('users.index')
                ->with('success', 'Usuário excluído com sucesso.');
        } catch (ValidationException $e) {
            throw $e;
        }
    }

    public function restore(Request $request, string $userUuid): RedirectResponse
    {
        $currentUser = $request->user();

        if (! $this->userService->canRestore($currentUser)) {
            abort(403, 'Você não tem permissão para reativar usuários.');
        }

        $password = $request->input('password');

        if (! $password) {
            abort(422, 'A senha é obrigatória para reativar um usuário.');
        }

        try {
            $this->userService->confirmRestore($userUuid, $password, $currentUser);

            return to_route('users.edit', $userUuid)
                ->with('success', 'Usuário reativado com sucesso.');
        } catch (ValidationException $e) {
            throw $e;
        }
    }

    public function checkEmail(Request $request): JsonResponse
    {
        $email = $request->input('email');

        if (! $email) {
            return response()->json(['exists' => false]);
        }

        $user = $this->userService->checkEmailExists($email);

        if (! $user) {
            return response()->json(['exists' => false]);
        }

        return response()->json([
            'exists' => true,
            'trashed' => $user->trashed(),
            'uuid' => $user->uuid,
            'name' => $user->name,
        ]);
    }

    public function loginAudits(Request $request, string $userUuid): RedirectResponse
    {
        $user = $this->userService->getByUuid($userUuid);

        session(['user_login_audit_filters' => [
            'per_page' => $request->input('per_page', 10),
        ]]);

        return to_route('users.edit', $user->uuid);
    }

    /**
     * Export user data (Master role only - LGPD).
     */
    public function export(Request $request, string $userUuid): StreamedResponse
    {
        $currentUser = $request->user();

        if ($currentUser->role?->name !== 'Master') {
            abort(403, 'Acesso negado.');
        }

        $user = $this->userService->getByUuidWithTrashed($userUuid);

        $data = $this->userService->exportData($user);

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, "dados_usuario_{$user->uuid}.json", ['Content-Type' => 'application/json']);
    }
}
