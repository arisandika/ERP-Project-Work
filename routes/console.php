<?php

use Illuminate\Support\Facades\Schedule;

// Schedule low stock check every day at 9 AM and 3 PM
Schedule::command('stock:check-low --notify')
    ->twiceDaily(9, 15)
    ->timezone('Asia/Jakarta')
    ->emailOutputOnFailure(env('ADMIN_EMAIL', 'admin@example.com'));

Schedule::command('do:auto-complete')
    ->dailyAt('00:00');

// HR SCHEDULER (PRESENSI)
// Jalankan setiap hari pukul 00:05 untuk membuat placeholder
Schedule::command('attendance:generate-placeholders')
    ->dailyAt('06:00')
    ->timezone('Asia/Jakarta');

// Jalankan setiap hari pukul 15:50 untuk finalisasi (menandai yang alpha)
Schedule::command('attendance:mark-absent')
    ->dailyAt('23:55')
    ->timezone('Asia/Jakarta');

// Jalankan setiap jam 00:01 dini hari
Schedule::command('leave:auto-expire')
    ->dailyAt('00:01')
    ->timezone('Asia/Jakarta');

// SALES SCHEDULER
Schedule::command('sales:process-expired-quotations')
    ->dailyAt('00:00')
    ->withoutOverlapping();
