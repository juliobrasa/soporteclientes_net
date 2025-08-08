<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ClientUser;
use App\Models\ClientLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Mostrar formulario de login
     */
    public function showLoginForm()
    {
        return view('client.auth.login');
    }

    /**
     * Procesar login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::guard('client')->attempt($credentials, $request->filled('remember'))) {
            $user = Auth::guard('client')->user();
            
            // Verificar que el usuario esté activo
            if (!$user->active) {
                Auth::guard('client')->logout();
                return back()->withErrors([
                    'email' => 'Tu cuenta ha sido desactivada.'
                ]);
            }

            // Verificar suscripción
            if (!$user->isSubscriptionActive()) {
                Auth::guard('client')->logout();
                return redirect()->route('client.subscription.expired')
                    ->with('user_email', $user->email);
            }

            // Actualizar último login
            $user->updateLastLogin();

            $request->session()->regenerate();

            return redirect()->intended(route('client.dashboard'));
        }

        return back()->withErrors([
            'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.'
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        Auth::guard('client')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('client.login');
    }

    /**
     * Mostrar formulario de registro
     */
    public function showRegistrationForm()
    {
        $clientLevels = ClientLevel::active()->ordered()->get();
        return view('client.auth.register', compact('clientLevels'));
    }

    /**
     * Procesar registro
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:client_users',
            'password' => 'required|string|min:8|confirmed',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'client_level_id' => 'required|exists:client_levels,id'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Crear usuario
        $user = ClientUser::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'company_name' => $request->company_name,
            'phone' => $request->phone,
            'client_level_id' => $request->client_level_id,
            'active' => true,
            'subscription_start' => now(),
            'subscription_end' => now()->addDays(30), // 30 días de prueba
            'subscription_status' => 'trial',
            'preferences' => [
                'language' => 'es',
                'timezone' => 'America/Mexico_City',
                'notifications' => [
                    'email' => true,
                    'push' => false
                ]
            ]
        ]);

        // Login automático
        Auth::guard('client')->login($user);

        return redirect()->route('client.dashboard')
            ->with('success', '¡Bienvenido! Tu cuenta ha sido creada exitosamente. Tienes 30 días de prueba gratuita.');
    }

    /**
     * Mostrar página de suscripción expirada
     */
    public function subscriptionExpired()
    {
        return view('client.auth.subscription-expired');
    }

    /**
     * API Login para AJAX
     */
    public function apiLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::guard('client')->attempt($credentials)) {
            $user = Auth::guard('client')->user();
            
            if (!$user->active) {
                Auth::guard('client')->logout();
                return response()->json([
                    'success' => false,
                    'error' => 'Usuario inactivo',
                    'message' => 'Tu cuenta ha sido desactivada'
                ], 403);
            }

            if (!$user->isSubscriptionActive()) {
                Auth::guard('client')->logout();
                return response()->json([
                    'success' => false,
                    'error' => 'Suscripción inactiva',
                    'message' => 'Tu suscripción ha expirado o ha sido cancelada',
                    'subscription_status' => $user->subscription_status,
                    'days_remaining' => $user->days_remaining
                ], 402);
            }

            $user->updateLastLogin();

            return response()->json([
                'success' => true,
                'message' => 'Login exitoso',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'company_name' => $user->company_name,
                    'client_level' => [
                        'name' => $user->clientLevel->name,
                        'display_name' => $user->clientLevel->display_name,
                        'features' => $user->clientLevel->features,
                        'modules' => $user->clientLevel->modules
                    ],
                    'subscription' => [
                        'status' => $user->subscription_status,
                        'days_remaining' => $user->days_remaining,
                        'is_trial' => $user->isOnTrial()
                    ]
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'Credenciales inválidas',
            'message' => 'Las credenciales proporcionadas no son correctas'
        ], 401);
    }

    /**
     * API Logout
     */
    public function apiLogout(Request $request)
    {
        Auth::guard('client')->logout();
        
        return response()->json([
            'success' => true,
            'message' => 'Logout exitoso'
        ]);
    }

    /**
     * Obtener información del usuario actual
     */
    public function me()
    {
        $user = Auth::guard('client')->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'No autenticado'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'company_name' => $user->company_name,
                'phone' => $user->phone,
                'client_level' => [
                    'name' => $user->clientLevel->name,
                    'display_name' => $user->clientLevel->display_name,
                    'features' => $user->clientLevel->features,
                    'modules' => $user->clientLevel->modules,
                    'max_hotels' => $user->max_hotels,
                    'max_reviews_per_month' => $user->max_reviews_per_month
                ],
                'subscription' => [
                    'status' => $user->subscription_status,
                    'start' => $user->subscription_start?->format('Y-m-d'),
                    'end' => $user->subscription_end?->format('Y-m-d'),
                    'days_remaining' => $user->days_remaining,
                    'is_trial' => $user->isOnTrial(),
                    'is_active' => $user->isSubscriptionActive()
                ],
                'hotels_count' => $user->activeHotels()->count(),
                'last_login_at' => $user->last_login_at?->format('Y-m-d H:i:s')
            ]
        ]);
    }
}