<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$schedule = app(Schedule::class);
$schedule->command('audit-logs:purge')->dailyAt('01:00');

$schedule->command('horizon:snapshot')->everyFiveMinutes();

$schedule->command('tasks:create-lembrete-agenda')->everyThirtyMinutes();

$schedule->command('tasks:create-lembrete-evento')->everyThirtyMinutes();

$schedule->command('notifications:notify-overdue-tasks')->everyThirtyMinutes();
