<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

// RUTAS PÚBLICAS - No requieren autenticación
// Registro de nuevos usuarios
Route::post('/register', [AuthController::class, 'register']);

// Inicio de sesión - genera token JWT
Route::post('/login', [AuthController::class, 'login']);

// RUTAS PROTEGIDAS - Requieren token JWT válido
// El middleware 'auth:api' verifica el token en cada petición
Route::middleware('auth:api')->group(function () {
    
    // Obtener información del usuario autenticado
    Route::get('/me', [AuthController::class, 'me']);
    
    // Cerrar sesión - invalida el token actual
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Renovar token JWT antes de que expire
    Route::post('/refresh', [AuthController::class, 'refresh']);
});

// RUTAS CON DOBLE PROTECCIÓN - Requieren token JWT Y rol de administrador
// Primero verifica autenticación, luego el rol 'admin'
Route::middleware(['auth:api', 'admin'])->group(function () {
    
    // Listar todos los usuarios (solo administradores)
    Route::get('/users', [UserController::class, 'index']);
});