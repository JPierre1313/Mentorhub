<?php

use App\Http\Controllers\Student\DashboardController;
use App\Http\Controllers\Student\CalendarController;
use App\Http\Controllers\Student\TaskController;
use App\Http\Controllers\Student\MessageController;
use App\Http\Controllers\Student\ProfileController;
use App\Http\Controllers\Student\NotificationController;
use Illuminate\Support\Facades\Route;

// Rutas para estudiantes
Route::middleware(['auth'])->prefix('student')->name('student.')->group(function () {
    // Dashboard principal
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Rutas para cursos
    Route::get('/courses', [DashboardController::class, 'courses'])->name('courses');
    Route::get('/courses/available', [DashboardController::class, 'availableCourses'])->name('courses.available');
    Route::get('/courses/{id}', [DashboardController::class, 'showCourse'])->name('courses.show');
    Route::get('/courses/{course}/progress', [DashboardController::class, 'courseProgress'])->name('course.progress');
    
    // Rutas de calendario
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
    Route::post('/calendar', [CalendarController::class, 'store'])->name('calendar.store');
    Route::put('/calendar/{id}', [CalendarController::class, 'update'])->name('calendar.update');
    Route::delete('/calendar/{id}', [CalendarController::class, 'destroy'])->name('calendar.destroy');
    Route::get('/calendar/events', [CalendarController::class, 'getEvents'])->name('calendar.events');
    
    // Rutas de perfil
    Route::get('/profile', [DashboardController::class, 'profile'])->name('profile');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.update-avatar');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');
    Route::post('/profile/preferences', [ProfileController::class, 'updatePreferences'])->name('profile.update-preferences');
    
    // Configuración de privacidad
    Route::patch('/settings/privacy', [ProfileController::class, 'updatePrivacySettings'])
        ->name('settings.update-privacy');
        
    // Configuración de apariencia
    Route::patch('/settings/appearance', [ProfileController::class, 'updateAppearanceSettings'])
        ->name('settings.update-appearance');
        
    // Configuración de aprendizaje
    Route::patch('/settings/learning', [ProfileController::class, 'updateLearningSettings'])
        ->name('settings.update-learning');
    
    // Rutas de notificaciones
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::patch('/settings/notifications', [ProfileController::class, 'updateNotificationPreferences'])
        ->name('settings.update-notifications');
    Route::get('/notifications/{notification}', [NotificationController::class, 'show'])->name('notifications.show');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    
    // Rutas de mensajes
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/create', [MessageController::class, 'create'])->name('messages.create');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::get('/messages/{id}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{id}/reply', [MessageController::class, 'reply'])->name('messages.reply');
    Route::post('/messages/{id}/toggle-read', [MessageController::class, 'toggleRead'])->name('messages.toggle-read');
    Route::post('/messages/mark-all-read', [MessageController::class, 'markAllAsRead'])->name('messages.mark-all-read');
    Route::delete('/messages/{id}', [MessageController::class, 'destroy'])->name('messages.destroy');
    
    // Rutas de tareas
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks');
    Route::get('/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::put('/tasks/{id}', [TaskController::class, 'update'])->name('tasks.update');
    Route::post('/tasks/{id}/toggle-status', [TaskController::class, 'toggleStatus'])->name('tasks.toggle-status');
    Route::delete('/tasks/{id}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::get('/tasks/json', [TaskController::class, 'getTasks'])->name('tasks.json');
    
    // Rutas de calificaciones
    Route::get('/grades', [DashboardController::class, 'grades'])->name('grades');
    
    // Ruta para la sección de mentores
    Route::get('/mentor', [DashboardController::class, 'mentor'])->name('mentor');
    
    // Rutas para configuración
    Route::get('/settings', [DashboardController::class, 'settings'])->name('settings');
});
