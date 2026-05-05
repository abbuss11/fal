<?php

use App\Http\Controllers\Client\DashboardController as ClientDashboardController;
use App\Http\Controllers\Client\ProjectFileController as ClientProjectFileController;
use App\Http\Controllers\Client\ProjectMessageController as ClientProjectMessageController;
use App\Http\Controllers\Client\ProjectController as ClientProjectController;
use App\Http\Controllers\Client\ProjectReportController as ClientProjectReportController;
use App\Http\Controllers\Client\SubtaskController as ClientSubtaskController;
use App\Http\Controllers\Client\TaskController as ClientTaskController;
use App\Http\Controllers\Client\TimesheetController as ClientTimesheetController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return redirect()->route('client.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->prefix('client')->name('client.')->group(function () {
    Route::get('/dashboard', ClientDashboardController::class)->name('dashboard');
    Route::get('/dashboard/snapshot', [ClientDashboardController::class, 'snapshot'])->name('dashboard.snapshot');
    Route::get('/projects', [ClientProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/{project}', [ClientProjectController::class, 'show'])->name('projects.show');
    Route::post('/projects/{project}/tasks/{task}/move', [ClientProjectController::class, 'moveTask'])
        ->middleware('permission:tasks.move')
        ->name('projects.tasks.move');
    Route::get('/projects/{project}/snapshot', [ClientProjectController::class, 'snapshot'])->name('projects.snapshot');
    Route::post('/projects/{project}/messages', [ClientProjectMessageController::class, 'store'])
        ->middleware('permission:messages.create')
        ->name('projects.messages.store');
    Route::post('/projects/{project}/files', [ClientProjectFileController::class, 'store'])
        ->middleware('permission:files.create')
        ->name('projects.files.store');
    Route::get('/projects/{project}/files/{projectFile}/download', [ClientProjectFileController::class, 'download'])
        ->middleware('permission:files.read')
        ->name('projects.files.download');
    Route::post('/projects/{project}/members', [ClientProjectController::class, 'storeMember'])
        ->middleware('permission:projects.manage_members')
        ->name('projects.members.store');
    Route::patch('/projects/{project}/members/{member}', [ClientProjectController::class, 'updateMember'])
        ->middleware('permission:projects.manage_members')
        ->name('projects.members.update');
    Route::delete('/projects/{project}/members/{member}', [ClientProjectController::class, 'removeMember'])
        ->middleware('permission:projects.manage_members')
        ->name('projects.members.remove');
    Route::get('/projects/{project}/report', [ClientProjectReportController::class, 'show'])->name('projects.report');
    Route::get('/projects/{project}/report/download', [ClientProjectReportController::class, 'download'])->name('projects.report.download');
    Route::get('/projects/{project}/report/download/pdf', [ClientProjectReportController::class, 'downloadPdf'])->name('projects.report.download-pdf');
    Route::get('/tasks', [ClientTaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/calendar', [ClientTaskController::class, 'calendar'])->name('tasks.calendar');
    Route::post('/tasks/{task}/comments', [ClientTaskController::class, 'storeComment'])
        ->middleware('permission:comments.create')
        ->name('tasks.comments.store');
    Route::post('/tasks/{task}/subtasks', [ClientSubtaskController::class, 'store'])
        ->middleware('permission:tasks.subtasks.manage')
        ->name('tasks.subtasks.store');
    Route::patch('/tasks/{task}/subtasks/{subtask}', [ClientSubtaskController::class, 'update'])
        ->middleware('permission:tasks.subtasks.manage')
        ->name('tasks.subtasks.update');

    Route::get('/timesheets', [ClientTimesheetController::class, 'index'])
        ->middleware('permission:timesheets.read')
        ->name('timesheets.index');
    Route::post('/timesheets', [ClientTimesheetController::class, 'store'])
        ->middleware('permission:timesheets.create')
        ->name('timesheets.store');
    Route::patch('/timesheets/{timesheet}', [ClientTimesheetController::class, 'update'])
        ->middleware('permission:timesheets.create')
        ->name('timesheets.update');
    Route::delete('/timesheets/{timesheet}', [ClientTimesheetController::class, 'destroy'])
        ->middleware('permission:timesheets.create')
        ->name('timesheets.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/admin', function () {
        abort_unless(auth()->user()?->isAdmin(), 403);

        return redirect('/abba');
    })->name('admin.portal');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
/*
// Route de test email - à supprimer après vérification
Route::get('/test-mail', function () {
    $to = request('to', 'idrissabba14@gmail.com');

    \Illuminate\Support\Facades\Mail::to($to)->send(new \App\Mail\TestMail());

    return response()->json([
        'success' => true,
        'message' => "Email envoyé à {$to}",
    ]);
})->name('test.mail');
*/
require __DIR__.'/auth.php';
