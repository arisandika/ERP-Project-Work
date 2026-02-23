<?php

use App\Http\Controllers\API\HR\AttendanceController;
use App\Http\Controllers\Inventory\StockReportController;
use App\Http\Controllers\Inventory\TransactionReportController;
use App\Livewire\ExternalDashboard;
use App\Livewire\ExternalLogin;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Sales\InvoiceVerificationController;
use App\Http\Controllers\Sales\DeliveryOrderTrackingController;
use App\Http\Controllers\Sales\DeliveryOrderPdfController;

// Auth
Route::middleware(['auth'])->group(function () {
    // Attendance Routes - HR MODULE
    Route::post('/attendance/clockin', [AttendanceController::class, 'clockIn'])->name('attendance.clockin');
    Route::post('/attendance/clockout', [AttendanceController::class, 'clockOut'])->name('attendance.clockout');
    Route::get('/hr/attendance/map-data', [AttendanceController::class, 'getMapData'])->name('api.hr.attendance.map-data');

    // Transaction Report PDF Download - INVENTORY MODULE
    Route::get('/inventory/transaction-report/download-pdf', [TransactionReportController::class, 'download'])->name('inventory.transaction-report.download-pdf');

    // Stock Report PDF Download - INVENTORY MODULE
    Route::get('/inventory/stock-report/download-pdf', [StockReportController::class, 'download'])->name('inventory.stock-report.download-pdf');

    // Delivery Order PDF Print - SALES MODULE
    Route::get('/print/delivery-order/{record}', [DeliveryOrderPdfController::class, 'print'])->name('print.delivery-order');
});


// Invoice Verification Routes - SALES MODULE
Route::get('/invoice/verify/{number}', [InvoiceVerificationController::class, 'showVerifyForm'])
    ->where('number', '.*')
    ->name('invoice.verify.form');

Route::post('/invoice/verify/{number}', [InvoiceVerificationController::class, 'submitVerify'])
    ->where('number', '.*')
    ->middleware('throttle:10,1')
    ->name('invoice.verify.submit');

Route::get('/invoice/view/{number}', [InvoiceVerificationController::class, 'showInvoice'])
    ->where('number', '.*')
    ->middleware('signed')
    ->name('invoice.view');

Route::get('/invoice/download/{record}', [InvoiceVerificationController::class, 'download'])
    ->name('invoice.download');


    // Tracking URL untuk Delivery Order - SALES MODULE
Route::get('/tracking/do/{do_number}', [DeliveryOrderTrackingController::class, 'show'])
    ->where('do_number', '.*')
    ->name('tracking.delivery-order');

Route::post('/tracking/do/{do_number}/terima', [DeliveryOrderTrackingController::class, 'markAsDelivered'])
    ->where('do_number', '.*')
    ->name('tracking.delivery-order.terima');

// External Dashboard Routes - PROJECT MODULE
Route::get('external/{token}', ExternalLogin::class)
    ->name('external.login');

Route::get('external/{token}/dashboard', ExternalDashboard::class)
    ->name('external.dashboard');
