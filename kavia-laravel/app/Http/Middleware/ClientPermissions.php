<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ClientPermissions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = Auth::guard('client')->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'No autenticado'
            ], 401);
        }

        // Verificar cada permiso requerido
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($user, $permission, $request)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Sin permisos',
                        'message' => 'No tienes permisos para acceder a esta funcionalidad',
                        'required_permission' => $permission,
                        'user_level' => $user->clientLevel->name,
                        'upgrade_message' => $this->getUpgradeMessage($permission)
                    ], 403);
                }
                
                return redirect()->route('client.dashboard')
                    ->with('error', 'No tienes permisos para acceder a esa sección. Considera actualizar tu plan.');
            }
        }

        return $next($request);
    }

    /**
     * Verificar si el usuario tiene un permiso específico
     */
    private function hasPermission($user, string $permission, Request $request): bool
    {
        // Verificar permisos por tipo
        switch ($permission) {
            case 'view_dashboard':
                return $user->hasFeature('dashboard_view');
                
            case 'view_reviews':
                return $user->hasFeature('reviews_view');
                
            case 'export_reports':
                return $user->hasFeature('export_reports');
                
            case 'ai_responses':
                return $user->hasFeature('ai_responses');
                
            case 'advanced_analytics':
                return $user->hasFeature('advanced_analytics');
                
            case 'competitor_analysis':
                return $user->hasFeature('competitor_analysis');
                
            case 'custom_alerts':
                return $user->hasFeature('custom_alerts');
                
            case 'module_resumen':
                return $user->hasModule('resumen');
                
            case 'module_otas':
                return $user->hasModule('otas');
                
            case 'module_reseñas':
                return $user->hasModule('reseñas');
                
            case 'hotel_access':
                $hotelId = $request->get('hotel_id');
                return $hotelId ? $user->hasAccessToHotel($hotelId) : true;
                
            default:
                return false;
        }
    }

    /**
     * Obtener mensaje de actualización según el permiso
     */
    private function getUpgradeMessage(string $permission): string
    {
        $messages = [
            'view_reviews' => 'Actualiza a Plan Básico o superior para ver reseñas detalladas',
            'export_reports' => 'Actualiza a Plan Profesional o superior para exportar reportes',
            'ai_responses' => 'Actualiza a Plan Profesional o superior para generar respuestas con IA',
            'advanced_analytics' => 'Actualiza a Plan Profesional o superior para análisis avanzados',
            'competitor_analysis' => 'Actualiza a Plan Empresarial para análisis de competencia',
            'module_otas' => 'Actualiza a Plan Profesional o superior para acceder a la sección de OTAs',
            'module_reseñas' => 'Esta funcionalidad no está disponible en tu plan actual'
        ];

        return $messages[$permission] ?? 'Actualiza tu plan para acceder a esta funcionalidad';
    }
}