<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Schedule low stock check every day at 9 AM and 3 PM
Schedule::command('stock:check-low --notify')
    ->twiceDaily(9, 15)
    ->timezone('Asia/Jakarta')
    ->emailOutputOnFailure(env('ADMIN_EMAIL', 'admin@example.com'));

Schedule::command('do:auto-complete')->dailyAt('00:00');

Schedule::command('attendance:generate-absent')
    ->dailyAt('13:55') // Jalankan setiap jam 11:55 malam
    ->timezone('Asia/Jakarta');