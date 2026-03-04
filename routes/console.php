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

// HR SCHEDULER (PRESENSI)
// Jalankan setiap hari pukul 00:05 untuk membuat placeholder
Schedule::command('attendance:generate-placeholders')
    ->dailyAt('00:05')
    ->timezone('Asia/Jakarta');

// Jalankan setiap hari pukul 15:50 untuk finalisasi (menandai yang alpha)
Schedule::command('attendance:mark-absent')
    ->dailyAt('15:50') 
    ->timezone('Asia/Jakarta');