<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\PacienteController;
use App\Http\Controllers\Admin\EstimuloController;
use App\Http\Controllers\Admin\EjercicioController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\Paciente\DashboardController as PacienteDashboard;
use App\Http\Controllers\Paciente\PlanController as PacientePlanController;

// Landing -> Login
Route::get('/', fn() => redirect()->route('login'));

// Auth
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Admin (Fisio)
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:fisio'])->group(function () {
    Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');

    Route::resource('pacientes', PacienteController::class);
    Route::resource('estimulos', EstimuloController::class)->except(['show']);
    Route::resource('ejercicios', EjercicioController::class)->except(['show']);
    Route::resource('planes', PlanController::class)->parameters([
        'planes' => 'plan'
    ]);
    Route::post('/chat/send', [ChatController::class, 'sendMessage'])->name('chat.send');
});

// Paciente
Route::prefix('paciente')->name('paciente.')->middleware(['auth', 'role:paciente'])->group(function () {
    Route::get('/dashboard', [PacienteDashboard::class, 'index'])->name('dashboard');
    Route::get('/plan/{plan}', [PacientePlanController::class, 'show'])->name('plan.show');
    Route::post('/plan/{plan}/feedback', [PacientePlanController::class, 'storeFeedback'])->name('plan.feedback');
});
