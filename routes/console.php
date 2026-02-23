<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule low stock check every day at 9 AM and 3 PM
Schedule::command('stock:check-low --notify')
    ->twiceDaily(9, 15)
    ->timezone('Asia/Jakarta')
    ->emailOutputOnFailure(env('ADMIN_EMAIL', 'admin@example.com'));

Schedule::command('do:auto-complete')->dailyAt('00:00');
