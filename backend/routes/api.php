<?php

use App\Http\Controllers\EnrollmentController;
use Illuminate\Support\Facades\Route;

Route::prefix('enrollments')->group(function () {
    Route::get('/', [EnrollmentController::class, 'index']);
    // IMPORTANT:
    // Must be before /{enrollment}
    Route::get('/export', [EnrollmentController::class, 'export']);
    Route::post('/', [EnrollmentController::class, 'store']);
    Route::get('/{enrollment}', [EnrollmentController::class, 'show']);
    Route::put('/{enrollment}', [EnrollmentController::class, 'update']);
    Route::delete('/{enrollment}', [EnrollmentController::class, 'destroy']);
});