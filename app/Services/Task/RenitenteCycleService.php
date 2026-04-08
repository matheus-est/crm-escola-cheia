<?php

declare(strict_types=1);

namespace App\Services\Task;

use App\Exceptions\RenitenteLimitReachedException;
use App\Models\Opportunity;
use Carbon\CarbonInterface;

class RenitenteCycleService
{
    public const int LIMIT = 6;

    /**
     * Calculate the next due_at for a renitente task and increment renitente_count.
     * count 0 → +1h · count 1–5 → +3h each · count 6 → throw RenitenteLimitReachedException
     *
     * @throws RenitenteLimitReachedException
     */
    public function nextDueAt(Opportunity $opportunity): CarbonInterface
    {
        $count = (int) $opportunity->renitente_count;

        if ($count >= self::LIMIT) {
            $opportunity->update(['renitente_count' => 0]);

            throw new RenitenteLimitReachedException(
                "Renitente limit reached for opportunity {$opportunity->uuid}"
            );
        }

        $hours = $count === 0 ? 1 : 3;

        $opportunity->increment('renitente_count');

        return now()->addHours($hours);
    }
}
