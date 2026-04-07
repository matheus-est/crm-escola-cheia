<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ActiveSchoolRequest;
use App\Services\Settings\ActiveSchoolService;
use Illuminate\Http\RedirectResponse;

class ActiveSchoolController extends Controller
{
    public function __construct(
        protected readonly ActiveSchoolService $activeSchoolService,
    ) {}

    public function store(ActiveSchoolRequest $request): RedirectResponse
    {
        $this->activeSchoolService->switch(
            $request->user(),
            $request->validated('school_uuid'),
        );

        return back();
    }
}