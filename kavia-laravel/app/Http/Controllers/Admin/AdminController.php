<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\AiProvider;
use App\Models\Prompt;
use App\Models\ExternalApi;
use App\Models\SystemLog;
use App\Models\ExtractionJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Dashboard principal del admin
     */
    public function dashboard()
    {
        // Estadísticas generales
        $stats = [
            'total_hotels' => Hotel::count(),
            'active_hotels' => Hotel::where('activo', true)->count(),
            'total_ai_providers' => AiProvider::count(),
            'active_ai_providers' => AiProvider::where('active', true)->count(),
            'total_prompts' => Prompt::count(),
            'total_external_apis' => ExternalApi::count(),
            'recent_logs' => SystemLog::latest()->limit(10)->get(),
            'recent_extractions' => ExtractionJob::latest()->limit(5)->get(),
        ];

        return view('admin.dashboard', compact('stats'));
    }

    /**
     * Gestión de hoteles
     */
    public function hotels()
    {
        $hotels = Hotel::with(['reviews' => function($query) {
            $query->select('hotel_id', 'rating');
        }])->orderBy('id', 'desc')->get();

        $hotels = $hotels->map(function ($hotel) {
            return [
                'id' => $hotel->id,
                'nombre_hotel' => $hotel->nombre_hotel,
                'hoja_destino' => $hotel->hoja_destino,
                'url_booking' => $hotel->url_booking,
                'max_reviews' => $hotel->max_reviews,
                'activo' => $hotel->activo,
                'total_reviews' => $hotel->reviews->count(),
                'avg_rating' => $hotel->reviews->count() > 0 
                    ? round($hotel->reviews->avg('rating'), 1) 
                    : 0,
                'created_at' => $hotel->created_at,
                'updated_at' => $hotel->updated_at,
            ];
        });

        return view('admin.hotels.index', compact('hotels'));
    }

    /**
     * Gestión de proveedores IA
     */
    public function aiProviders()
    {
        $providers = AiProvider::orderBy('id', 'desc')->get();
        return view('admin.ai-providers.index', compact('providers'));
    }

    /**
     * Gestión de prompts
     */
    public function prompts()
    {
        $prompts = Prompt::orderBy('id', 'desc')->get();
        return view('admin.prompts.index', compact('prompts'));
    }

    /**
     * Gestión de APIs externas
     */
    public function externalApis()
    {
        $apis = ExternalApi::orderBy('id', 'desc')->get();
        return view('admin.external-apis.index', compact('apis'));
    }

    /**
     * Logs del sistema
     */
    public function systemLogs()
    {
        $logs = SystemLog::orderBy('id', 'desc')->paginate(50);
        return view('admin.system-logs.index', compact('logs'));
    }

    /**
     * Trabajos de extracción
     */
    public function extractionJobs()
    {
        $jobs = ExtractionJob::with('hotel')->orderBy('id', 'desc')->paginate(20);
        return view('admin.extraction-jobs.index', compact('jobs'));
    }

    /**
     * Herramientas del sistema
     */
    public function tools()
    {
        // Estadísticas de la base de datos
        $dbStats = [
            'total_tables' => collect(DB::select('SHOW TABLES'))->count(),
            'database_size' => $this->getDatabaseSize(),
            'total_records' => $this->getTotalRecords(),
        ];

        return view('admin.tools.index', compact('dbStats'));
    }

    /**
     * Obtener tamaño de la base de datos
     */
    private function getDatabaseSize()
    {
        try {
            $result = DB::select("
                SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
            ");
            return $result[0]->size_mb ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Obtener total de registros
     */
    private function getTotalRecords()
    {
        try {
            $hotels = Hotel::count();
            $reviews = DB::table('reviews')->count();
            $logs = SystemLog::count();
            return $hotels + $reviews + $logs;
        } catch (\Exception $e) {
            return 0;
        }
    }
}