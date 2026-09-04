<?php

use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\MobileDataController;
use App\Http\Controllers\Api\V1\HomeworkController;
use App\Http\Controllers\Api\V1\MarksController;
use App\Http\Middleware\EnsureMobileAccess;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:api')->group(function () {
    Route::post('auth/school', [AuthController::class, 'school'])->middleware('throttle:20,1');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('auth/otp/verify', [AuthController::class, 'verifyOtp'])->middleware('throttle:10,1');
    Route::post('auth/otp/resend', [AuthController::class, 'resendOtp'])->middleware('throttle:5,1');
    Route::post('auth/password/forgot', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('auth/password/reset', [AuthController::class, 'resetPassword'])->middleware('throttle:10,1');
    Route::middleware(['auth:sanctum', EnsureMobileAccess::class])->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('dashboard', [MobileDataController::class, 'dashboard']);
        Route::get('timetable', [MobileDataController::class, 'timetable']);
        Route::get('events', [MobileDataController::class, 'events']);
        Route::get('announcements', [MobileDataController::class, 'announcements']);
        Route::get('results', [MobileDataController::class, 'results']);
        Route::get('payments', [MobileDataController::class, 'payments'])->name('api.mobile.payments');
        Route::get('children', [MobileDataController::class, 'children']);
        Route::get('activities', [MobileDataController::class, 'activities']);
        Route::get('leave-requests', [MobileDataController::class, 'leaveRequests'])->name('api.mobile.leave.index');
        Route::post('leave-requests', [MobileDataController::class, 'storeLeaveRequest'])->name('api.mobile.leave.store');
        Route::get('teaching-assignments', [MobileDataController::class, 'teachingAssignments']);
        Route::get('attendance', [AttendanceController::class, 'index']);
        Route::post('attendance', [AttendanceController::class, 'store']);
        Route::get('homework', [HomeworkController::class, 'index']);
        Route::post('homework', [HomeworkController::class, 'store']);
        Route::get('homework/{assignment}', [HomeworkController::class, 'show']);
        Route::post('homework/{assignment}/submit', [HomeworkController::class, 'submit']);
        Route::post('homework/{assignment}/submissions/{submission}/review', [HomeworkController::class, 'review']);
        Route::get('exam-papers', [MarksController::class, 'index']);
        Route::get('exam-papers/{paper}/marks', [MarksController::class, 'show']);
        Route::put('exam-papers/{paper}/marks', [MarksController::class, 'update']);
        Route::post('exam-papers/{paper}/submit', [MarksController::class, 'submit']);
    });
});
