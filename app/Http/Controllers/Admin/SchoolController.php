<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\School\SchoolStoreRequest;
use App\Http\Requests\School\SchoolUpdateRequest;
use App\Models\School;
use App\Services\School\SchoolService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SchoolController extends Controller
{
    public function __construct(
        protected readonly SchoolService $schoolService,
    ) {}

    public function index(Request $request): Response
    {
        $schools = $this->schoolService->list($request->only(['razao_social', 'status']));

        return Inertia::render('admin/Schools/Index', ['schools' => $schools]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/Schools/Create');
    }

    public function store(SchoolStoreRequest $request): RedirectResponse
    {
        Gate::authorize('create', School::class);

        $this->schoolService->create($request->validated());

        return to_route('admin.schools.index');
    }

    public function edit(School $school): Response
    {
        Gate::authorize('view', $school);

        return Inertia::render('admin/Schools/Edit', ['school' => $school->load(['units', 'users.role'])]);
    }

    public function update(SchoolUpdateRequest $request, School $school): RedirectResponse
    {
        Gate::authorize('update', $school);

        $this->schoolService->update($school, $request->validated());

        return to_route('admin.schools.edit', $school);
    }

    public function destroy(School $school): RedirectResponse
    {
        Gate::authorize('delete', $school);

        $this->schoolService->destroy($school);

        return to_route('admin.schools.index');
    }
}
