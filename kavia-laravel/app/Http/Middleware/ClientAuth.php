<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ClientUser;
use Symfony\Component\HttpFoundation\Response;

class ClientAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si hay un usuario cliente autenticado
        if (!Auth::guard('client')->check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'No autenticado',
                    'message' => 'Debes iniciar sesión para acceder a esta funcionalidad'
                ], 401);
            }
            
            return redirect()->route('client.login');
        }

        $user = Auth::guard('client')->user();

        // Verificar que el usuario esté activo
        if (!$user->active) {
            Auth::guard('client')->logout();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Usuario inactivo',
                    'message' => 'Tu cuenta ha sido desactivada'
                ], 403);
            }
            
            return redirect()->route('client.login')
                ->withErrors(['email' => 'Tu cuenta ha sido desactivada.']);
        }

        // Verificar que la suscripción esté activa
        if (!$user->isSubscriptionActive()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Suscripción inactiva',
                    'message' => 'Tu suscripción ha expirado o ha sido cancelada',
                    'subscription_status' => $user->subscription_status,
                    'days_remaining' => $user->days_remaining
                ], 402);
            }
            
            return redirect()->route('client.subscription.expired');
        }

        return $next($request);
    }
}