<?php

use App\Http\Controllers\API\HR\AttendanceController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });


Route::middleware(['auth'])->group(function () {
    Route::post('/attendance/clockin', [AttendanceController::class, 'clockIn'])->name('attendance.clockin');
    Route::post('/attendance/clockout', [AttendanceController::class, 'clockOut'])->name('attendance.clockout');
});