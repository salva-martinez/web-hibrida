<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\PacienteController;
use App\Http\Controllers\Admin\EstimuloController;
use App\Http\Controllers\Admin\EjercicioController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\Admin\AnalisisController;
use App\Http\Controllers\Admin\PapeleraController;
use App\Http\Controllers\Paciente\DashboardController as PacienteDashboard;
use App\Http\Controllers\Paciente\PlanController as PacientePlanController;

Route::get('/', function () {
    return view('auth.login');
});

// Auth
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->middleware('guest');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Registro abierto de fisioterapeutas (no requiere invitación).
// Cada fisio empieza con su propia cartera de pacientes y su propio catálogo.
Route::get('/register', [RegisterController::class, 'showForm'])->name('register')->middleware(['guest', 'throttle:20,1']);
Route::post('/register', [RegisterController::class, 'register'])->middleware(['guest', 'throttle:10,1']);

// Verificación de email (obligatoria para entrar en el panel del fisio)
Route::get('/email/verify', [VerifyEmailController::class, 'notice'])->middleware('auth')->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, 'verify'])
    ->middleware(['auth', 'signed', 'throttle:6,1'])
    ->name('verification.verify');
Route::post('/email/verification-notification', [VerifyEmailController::class, 'resend'])
    ->middleware('auth')
    ->name('verification.send');

// Admin (Fisio)
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:fisio', 'verified'])->group(function () {
    Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');

    Route::resource('pacientes', PacienteController::class);
    Route::resource('estimulos', EstimuloController::class)->except(['show']);
    Route::resource('ejercicios', EjercicioController::class)->except(['show']);
    Route::resource('planes', PlanController::class)->parameters([
        'planes' => 'plan'
    ]);
    Route::post('/chat/send', [ChatController::class, 'sendMessage'])->name('chat.send')->middleware('throttle:10,1');
    Route::get('/feedback/{feedback}/analisis', [AnalisisController::class, 'show'])->name('feedback.analisis')->middleware('throttle:60,1');

    // Papelera: restauración de elementos desactivados
    Route::prefix('papelera')->name('papelera.')->group(function () {
        Route::get('/', [PapeleraController::class, 'index'])->name('index');
        Route::post('/pacientes/{id}/restaurar', [PapeleraController::class, 'restorePaciente'])->name('paciente.restore');
        Route::post('/planes/{id}/restaurar', [PapeleraController::class, 'restorePlan'])->name('plan.restore');
        Route::post('/estimulos/{id}/restaurar', [PapeleraController::class, 'restoreEstimulo'])->name('estimulo.restore');
        Route::post('/ejercicios/{id}/restaurar', [PapeleraController::class, 'restoreEjercicio'])->name('ejercicio.restore');
    });
});

// Paciente
Route::prefix('paciente')->name('paciente.')->middleware(['auth', 'role:paciente'])->group(function () {
    Route::get('/dashboard', [PacienteDashboard::class, 'index'])->name('dashboard');
    Route::get('/plan/{plan}', [PacientePlanController::class, 'show'])->name('plan.show');
    Route::post('/plan/{planDia}/feedback', [PacientePlanController::class, 'storeFeedback'])->name('plan.feedback')->middleware('throttle:10,1');
});
