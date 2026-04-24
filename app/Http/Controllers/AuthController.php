<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * REGISTRO DE NUEVOS USUARIOS
     * 
     * Endpoint: POST /api/register
     * 
     * @param Request $request (contiene name, email, password, password_confirmation)
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        // PASO 1: Validar los datos que vienen del formulario/request
        // - name: obligatorio, texto, máximo 255 caracteres
        // - email: obligatorio, formato email, máximo 255, debe ser único en la tabla users
        // - password: obligatorio, texto, mínimo 6 caracteres, debe tener confirmación
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed', // 'confirmed' busca campo password_confirmation
        ]);

        // PASO 2: Si la validación falla, devolver errores con código HTTP 422 (Unprocessable Entity)
        if ($validator->fails()) {
            return response()->json([
                'status' => false,           // Indicamos que fue un error
                'message' => 'Error de validación',  // Mensaje amigable
                'errors' => $validator->errors()    // Detalles de cada error
            ], 422);  // Código 422 = Los datos enviados no son válidos
        }

        // PASO 3: Crear el nuevo usuario en la base de datos
        // El método create() asigna automáticamente los campos al modelo User
        // Nota: La contraseña se encripta automáticamente gracias al mutator en el modelo User
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,  // Se encripta automáticamente
            'role' => 'user',  // Asignamos rol por defecto (puede ser 'admin', 'editor', etc.)
        ]);

        // PASO 4: Devolver respuesta exitosa con código HTTP 201 (Created)
        return response()->json([
            'status' => true,                          // Éxito
            'message' => 'Usuario registrado correctamente',  // Mensaje de confirmación
            'user' => $user                            // Datos del usuario creado
        ], 201);  // Código 201 = Recurso creado exitosamente
    }

    /**
     * INICIO DE SESIÓN
     * 
     * Endpoint: POST /api/login
     * 
     * @param Request $request (contiene email y password)
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        // PASO 1: Extraer solo email y password del request
        // No queremos otros campos como 'remember_me' o datos extra
        $credentials = $request->only('email', 'password');

        // PASO 2: Intentar autenticar al usuario con las credenciales
        // auth('api') = usa el guard 'api' configurado con JWT
        // attempt() = verifica email+password y genera un token si son correctos
        if (!$token = auth('api')->attempt($credentials)) {
            // Si falla, devolver error 401 (Unauthorized)
            return response()->json([
                'status' => false,
                'message' => 'Credenciales incorrectas'  // Email o password inválidos
            ], 401);  // Código 401 = No autorizado
        }

        // PASO 3: Si todo está bien, devolver el token JWT
        return $this->respondWithToken($token);
    }

    /**
     * OBTENER DATOS DEL USUARIO AUTENTICADO
     * 
     * Endpoint: GET /api/me
     * Requiere: Token JWT en el header (Authorization: Bearer token)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        // auth('api')->user() obtiene el usuario asociado al token enviado
        // Laravel automáticamente valida que el token sea válido y no haya expirado
        return response()->json([
            'status' => true,
            'data' => auth('api')->user()  // Devuelve nombre, email, rol, etc.
        ]);
    }

    /**
     * CERRAR SESIÓN (INVALIDAR TOKEN)
     * 
     * Endpoint: POST /api/logout
     * Requiere: Token JWT en el header
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        // Eliminar/invalidar el token actual
        // El token ya no servirá para futuras peticiones
        auth('api')->logout();

        return response()->json([
            'status' => true,
            'message' => 'Sesión cerrada correctamente'
        ]);
    }

    /**
     * RENOVAR TOKEN JWT (cuando está por expirar)
     * 
     * Endpoint: POST /api/refresh
     * Requiere: Token JWT válido (aunque esté cerca de expirar)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        // Generar un nuevo token basado en el token actual
        // Esto evita que el usuario tenga que volver a iniciar sesión
        return $this->respondWithToken(auth('api')->refresh());
    }

    /**
     * MÉTODO AUXILIAR: Formatear la respuesta del token
     * 
     * Este método es reutilizado por login() y refresh()
     * para mantener el formato consistente.
     * 
     * @param string $token (el token JWT generado)
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'status' => true,
            'access_token' => $token,               // El token JWT
            'token_type' => 'bearer',               // Tipo de token (estándar)
            'expires_in' => auth('api')->factory()->getTTL() * 60  // Tiempo de vida en segundos
            // getTTL() devuelve minutos (ej: 60) y lo multiplicamos por 60 para tener segundos (3600 = 1 hora)
        ]);
    }
}