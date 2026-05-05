<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\MobileDeviceController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\TimesheetController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('api.token:*')->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/projects', [ProjectController::class, 'index']);
        Route::get('/projects/{project}', [ProjectController::class, 'show']);

        Route::get('/tasks', [TaskController::class, 'index']);
        Route::patch('/tasks/{task}/status', [TaskController::class, 'quickUpdateStatus']);

        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::patch('/notifications/{notificationId}/read', [NotificationController::class, 'markAsRead']);

        Route::post('/mobile/device-token', [MobileDeviceController::class, 'registerToken']);
        Route::post('/mobile/device-token/revoke', [MobileDeviceController::class, 'revokeToken']);

        Route::get('/timesheets', [TimesheetController::class, 'index']);
        Route::post('/timesheets', [TimesheetController::class, 'store']);
        Route::patch('/timesheets/{timesheet}', [TimesheetController::class, 'update']);
        Route::delete('/timesheets/{timesheet}', [TimesheetController::class, 'destroy']);
    });
});
