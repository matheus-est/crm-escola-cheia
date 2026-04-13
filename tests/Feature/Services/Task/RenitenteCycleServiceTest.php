<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Task;

use App\Exceptions\RenitenteLimitReachedException;
use App\Models\Opportunity;
use App\Services\Task\RenitenteCycleService;

it('adds 1 hour and increments count when count is 0', function () {
    $opportunity = Opportunity::factory()->create(['renitente_count' => 0]);
    $service = new RenitenteCycleService;

    $dueAt = $service->nextDueAt($opportunity);

    expect($dueAt->format('Y-m-d H'))->toBe(now()->addHour()->format('Y-m-d H'));
    expect($opportunity->fresh()->renitente_count)->toBe(1);
});

it('adds 3 hours and increments count when count is between 1 and 5', function () {
    $opportunity = Opportunity::factory()->create(['renitente_count' => 3]);
    $service = new RenitenteCycleService;

    $dueAt = $service->nextDueAt($opportunity);

    expect($dueAt->format('Y-m-d H'))->toBe(now()->addHours(3)->format('Y-m-d H'));
    expect($opportunity->fresh()->renitente_count)->toBe(4);
});

it('throws RenitenteLimitReachedException and resets count when count reaches 6', function () {
    $opportunity = Opportunity::factory()->create(['renitente_count' => 6]);
    $service = new RenitenteCycleService;

    expect(fn () => $service->nextDueAt($opportunity))
        ->toThrow(RenitenteLimitReachedException::class);

    expect($opportunity->fresh()->renitente_count)->toBe(0);
});

it('adds 3 hours when count is 1', function () {
    $opportunity = Opportunity::factory()->create(['renitente_count' => 1]);
    $service = new RenitenteCycleService;

    $dueAt = $service->nextDueAt($opportunity);

    expect($dueAt->format('Y-m-d H'))->toBe(now()->addHours(3)->format('Y-m-d H'));
    expect($opportunity->fresh()->renitente_count)->toBe(2);
});

it('adds 3 hours when count is 5', function () {
    $opportunity = Opportunity::factory()->create(['renitente_count' => 5]);
    $service = new RenitenteCycleService;

    $dueAt = $service->nextDueAt($opportunity);

    expect($dueAt->format('Y-m-d H'))->toBe(now()->addHours(3)->format('Y-m-d H'));
    expect($opportunity->fresh()->renitente_count)->toBe(6);
});

it('resets count to 0 after reaching limit and does not return a due date', function () {
    $opportunity = Opportunity::factory()->create(['renitente_count' => 6]);
    $service = new RenitenteCycleService;

    $threw = false;
    try {
        $service->nextDueAt($opportunity);
    } catch (RenitenteLimitReachedException) {
        $threw = true;
    }

    expect($threw)->toBeTrue();
    expect($opportunity->fresh()->renitente_count)->toBe(0);
});
