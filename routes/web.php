<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ManualFailedResponseController;
use App\Http\Controllers\ManualResponsePhotoImportController;
use App\Http\Controllers\ResponsePhotoImportController;
use App\Http\Controllers\TransferReportController;
use App\Http\Controllers\TransferReportExportController;
use Illuminate\Support\Facades\Route;

Route::post('/locale', LocaleController::class)->name('locale.update');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/', TransferReportController::class)->name('reports.index');
    Route::get('/reports/export', TransferReportExportController::class)->middleware('can:export-transfer-reports')->name('reports.export');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::middleware('can:respond-to-transfers')->group(function (): void {
        Route::post('/response-photo-imports', ResponsePhotoImportController::class)->name('response-photo-imports.store');
        Route::post('/manual-response-photo-imports', ManualResponsePhotoImportController::class)->name('manual-response-photo-imports.store');
        Route::post('/manual-failed-responses', ManualFailedResponseController::class)->name('manual-failed-responses.store');
    });

    Route::prefix('admin')->name('admin.')->middleware('can:manage-users')->group(function (): void {
        Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
    });
});
