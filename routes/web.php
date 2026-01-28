<?php

use App\Http\Controllers\API\HR\AttendanceController;
use App\Http\Controllers\Inventory\StockReportController;
use App\Http\Controllers\Inventory\TransactionReportController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Sales\PrintController;


// Route::get('/', function () {
//     return view('welcome');
// });

Route::middleware(['auth'])->group(function () {
    // Attendance Routes - HR MODULE
    Route::post('/attendance/clockin', [AttendanceController::class, 'clockIn'])->name('attendance.clockin');
    Route::post('/attendance/clockout', [AttendanceController::class, 'clockOut'])->name('attendance.clockout');
    Route::get('/hr/attendance/map-data', [AttendanceController::class, 'getMapData'])->name('api.hr.attendance.map-data');

    // Transaction Report PDF Download - INVENTORY MODULE
    Route::get('/inventory/transaction-report/download-pdf', [TransactionReportController::class, 'download'])->name('inventory.transaction-report.download-pdf');

    // Stock Report PDF Download - INVENTORY MODULE
    Route::get('/inventory/stock-report/download-pdf', [StockReportController::class, 'download'])->name('inventory.stock-report.download-pdf');

    // Sales Print Routes - SALES MODULE
    Route::get('/print/delivery-order/{record}', [PrintController::class, 'deliveryOrder'])
        ->name('print.delivery-order');

    Route::get('/invoice/verify/{number}', [PrintController::class, 'verifyInvoice'])
        ->name('invoice.verify')
        ->where('number', '.*');
});
