<?php

use App\Http\Controllers\API\HR\AttendanceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/attendances', [AttendanceController::class, 'index']);
