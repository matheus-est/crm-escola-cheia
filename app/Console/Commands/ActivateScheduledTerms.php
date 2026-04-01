<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Acl\TermService;
use Illuminate\Console\Command;

class ActivateScheduledTerms extends Command
{
    protected $signature = 'terms:activate-scheduled';

    protected $description = 'Activates term versions whose effective_at date has been reached. Run daily by the scheduler.';

    public function __construct(
        protected readonly TermService $termService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $activated = $this->termService->activateScheduledTerms();

        if (empty($activated)) {
            $this->info('Nenhum termo agendado para ativar.');

            return self::SUCCESS;
        }

        foreach ($activated as $term) {
            $this->info("Termo ativado: [{$term->version}] {$term->title} (vigência: {$term->effective_at->toDateString()})");
        }

        return self::SUCCESS;
    }
}