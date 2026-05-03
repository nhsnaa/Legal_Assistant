<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CaseController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/login', [LoginController::class, 'store']);
    Route::post('/register', [RegisterController::class, 'store']);
    Route::post('/forgot-password', [LoginController::class, 'requestPasswordReset'])->name('password.request');
});

Route::post('/logout', function () {
    session()->forget(['firebase_user_id', 'user_role', 'user_name', 'workspace_id', 'workspace_owner_id', 'workspace_status', 'email', 'first_name', 'workspace_security_settings']);
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('login');
})->name('logout')->middleware('firebase.auth');

Route::middleware('firebase.auth')->group(function () {
    // Dashboard & Settings
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/ai-suggestions', [DashboardController::class, 'generateAiSuggestions'])->name('dashboard.ai-suggestions');
    Route::post('/dashboard/time-entries', [DashboardController::class, 'storeTimeEntry'])->name('dashboard.time-entries.store');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::post('/settings/notifications', [SettingsController::class, 'updateNotificationSettings'])->name('settings.notifications.update');
    Route::post('/settings/security', [SettingsController::class, 'updateSecuritySettings'])->name('settings.security.update');
    Route::post('/settings/staff', [SettingsController::class, 'storeStaff'])->name('settings.staff.store');
    Route::post('/settings/staff/invite', [SettingsController::class, 'inviteStaff'])->name('settings.staff.invite');
    Route::post('/settings/check-email', [SettingsController::class, 'checkEmail'])->name('settings.check-email');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-read/{id}', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('/notifications/accept/{id}', [NotificationController::class, 'acceptInvitation'])->name('notifications.accept');
    Route::post('/notifications/{id}/reset-password', [NotificationController::class, 'resetPasswordRequest'])->name('notifications.reset-password');
    Route::delete('/settings/staff/{id}', [SettingsController::class, 'destroyStaff'])->name('settings.staff.destroy');
    Route::delete('/settings/account', [SettingsController::class, 'destroyAccount'])->name('settings.account.destroy');
    Route::post('/settings/password', [SettingsController::class, 'updatePassword'])->name('password.update');

    // Clients - Using {id} for Firebase Key
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::get('/clients/{id}', [ClientController::class, 'show'])->name('clients.show');
    Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
    Route::put('/clients/{id}', [ClientController::class, 'update'])->name('clients.update');
    Route::patch('/clients/{id}/archive', [ClientController::class, 'toggleArchive'])->name('clients.archive');
    Route::delete('/clients/{id}', [ClientController::class, 'destroy'])->name('clients.destroy');

    // Cases - Using {id} for Firebase Key
    Route::get('/cases', [CaseController::class, 'index'])->name('cases.index');
    Route::post('/cases', [CaseController::class, 'store'])->name('cases.store');
    Route::get('/cases/{id}', [CaseController::class, 'show'])->name('cases.show');
    Route::put('/cases/{id}', [CaseController::class, 'update'])->name('cases.update');
    Route::patch('/cases/{id}/archive', [CaseController::class, 'toggleArchive'])->name('cases.archive');
    Route::delete('/cases/{id}', [CaseController::class, 'destroy'])->name('cases.destroy');

    // Schedule
    Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule');
    Route::post('/schedule/events', [ScheduleController::class, 'storeEvent'])->name('schedule.events.store');
    Route::put('/schedule/events/{id}', [ScheduleController::class, 'updateEvent'])->name('schedule.events.update');
    Route::delete('/schedule/events/{id}', [ScheduleController::class, 'destroyEvent'])->name('schedule.events.destroy');
    Route::post('/schedule/time-entries', [ScheduleController::class, 'storeTimeEntry'])->name('schedule.time-entries.store');
    Route::put('/schedule/time-entries/{id}', [ScheduleController::class, 'updateTimeEntry'])->name('schedule.time-entries.update');
    Route::delete('/schedule/time-entries/{id}', [ScheduleController::class, 'destroyTimeEntry'])->name('schedule.time-entries.destroy');

    // Documents
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/documents/create', [DocumentController::class, 'create'])->name('documents.create');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{id}', [DocumentController::class, 'show'])->name('documents.show');
    Route::get('/documents/{id}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
    Route::put('/documents/{id}', [DocumentController::class, 'update'])->name('documents.update');
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    // Search
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/{id}/accept', [NotificationController::class, 'acceptInvitation'])->name('notifications.accept');
});
