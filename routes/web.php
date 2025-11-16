<?php

use App\Http\Controllers\API\HR\AttendanceController;
use App\Http\Controllers\Inventory\StockReportPdfController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::middleware(['auth'])->group(function () {
    Route::post('/attendance/clockin', [AttendanceController::class, 'clockIn'])->name('attendance.clockin');
    Route::post('/attendance/clockout', [AttendanceController::class, 'clockOut'])->name('attendance.clockout');
    Route::get('/hr/attendance/map-data', [AttendanceController::class, 'getMapData'])->name('api.hr.attendance.map-data');

    Route::get('/inventory/transaction-report/download-pdf', [\App\Http\Controllers\Inventory\TransactionReportController::class, 'downloadPdf'])->name('inventory.transaction-report.download-pdf');
    Route::get('/inventory/stock-report/download-pdf', [\App\Http\Controllers\Inventory\TransactionReportController::class, 'downloadStockReportPdf'])->name('inventory.stock-report.download-pdf');

    Route::get('/inventory/stock-report/download-pdf', [StockReportPdfController::class, 'download'])->name('inventory.stock-report.download-pdf');

});
