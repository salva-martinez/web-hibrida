<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\PacienteController;
use App\Http\Controllers\Admin\EstimuloController;
use App\Http\Controllers\Admin\EjercicioController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\Admin\FisioInvitationController;
use App\Http\Controllers\Paciente\DashboardController as PacienteDashboard;
use App\Http\Controllers\Paciente\PlanController as PacientePlanController;

Route::get('/', function () {
    return view('auth.login');
});

// Auth
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->middleware('guest');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Registro de fisios por invitación
Route::get('/register/{token}', [RegisterController::class, 'showForm'])->name('register.show')->middleware(['guest', 'throttle:20,1']);
Route::post('/register/{token}', [RegisterController::class, 'register'])->name('register.store')->middleware(['guest', 'throttle:10,1']);

// Admin (Fisio)
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:fisio'])->group(function () {
    Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');

    Route::resource('pacientes', PacienteController::class);
    Route::resource('estimulos', EstimuloController::class)->except(['show']);
    Route::resource('ejercicios', EjercicioController::class)->except(['show']);
    Route::resource('planes', PlanController::class)->parameters([
        'planes' => 'plan'
    ]);
    Route::post('/chat/send', [ChatController::class, 'sendMessage'])->name('chat.send')->middleware('throttle:10,1');

    Route::resource('invitaciones', FisioInvitationController::class)->only(['index', 'store', 'destroy']);
});

// Paciente
Route::prefix('paciente')->name('paciente.')->middleware(['auth', 'role:paciente'])->group(function () {
    Route::get('/dashboard', [PacienteDashboard::class, 'index'])->name('dashboard');
    Route::get('/plan/{plan}', [PacientePlanController::class, 'show'])->name('plan.show');
    Route::post('/plan/{planDia}/feedback', [PacientePlanController::class, 'storeFeedback'])->name('plan.feedback')->middleware('throttle:10,1');
});
