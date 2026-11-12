<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CertificadoController;
use App\Http\Controllers\ClearCacheController;
use App\Http\Controllers\CredencialController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\GradoController;
use App\Http\Controllers\InscripcionController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PaisController;
// Rutas del catálogo de países y del listado completo de inscritos para impresión.
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\UsuarioController;
use App\Http\Middleware\ProjectAccess;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');
Route::get('/credenciales/{inscripcion}/verificar', [CredencialController::class, 'verificar'])->middleware('signed')->name('credenciales.verificar');
Route::get('/credenciales/{inscripcion}/foto', [CredencialController::class, 'foto'])->middleware('signed')->name('credenciales.foto');
Route::get('/credenciales/{inscripcion}/imagen', [CredencialController::class, 'imagen'])->middleware('signed')->name('credenciales.imagen');
Route::get('/', fn () => redirect()->route(auth()->user()->isSuperAdmin() ? 'dashboard' : 'inscripciones.index'))->middleware('auth');
Route::middleware(['auth', ProjectAccess::class])->group(function () {

    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/cache/clear', ClearCacheController::class)->name('cache.clear');
    Route::get('/eventos/{evento}/imagen', [EventoController::class, 'imagen'])->name('eventos.imagen');
    Route::resource('personas', PersonaController::class)->except('show');
    Route::get('/personas/{persona}/foto', [PersonaController::class, 'foto'])->name('personas.foto');
    Route::get('/personas/{persona}/credencial', [CredencialController::class, 'show'])->name('personas.credencial');
    Route::resource('eventos', EventoController::class)->except('show');
    Route::get('/inscripciones/imprimir', [InscripcionController::class, 'imprimir'])->name('inscripciones.imprimir');
    Route::get('/inscripciones/{inscripcion}/pagos', [PagoController::class, 'create'])->name('inscripciones.pagos.create');
    Route::post('/inscripciones/{inscripcion}/pagos', [PagoController::class, 'store'])->name('inscripciones.pagos.store');
    Route::delete('/inscripciones/{inscripcion}/pagos/{pago}', [PagoController::class, 'destroy'])->name('inscripciones.pagos.destroy');
    Route::resource('inscripciones', InscripcionController::class)->parameters(['inscripciones' => 'inscripcion'])->except('show');
    Route::get('/inscripciones/{inscripcion}/credencial', [CredencialController::class, 'porInscripcion'])->name('inscripciones.credencial');
    Route::get('/inscripciones/{inscripcion}/certificado', [CertificadoController::class, 'show'])->name('inscripciones.certificado');
    Route::resource('grados', GradoController::class)->except('show');
    Route::resource('paises', PaisController::class)->parameters(['paises' => 'pais'])->except('show');

    Route::resource('usuarios', UsuarioController::class)->parameters(['usuarios' => 'usuario'])->except('show');
});
