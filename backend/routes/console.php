<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('peladas:open-sessions')
    ->dailyAt('03:00')
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping()
    ->runInBackground();
