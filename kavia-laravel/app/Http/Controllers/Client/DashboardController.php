<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the client dashboard
     */
    public function index()
    {
        $hotels = Hotel::where('activo', 1)
            ->orderBy('nombre_hotel')
            ->get();

        return view('client.dashboard.index', compact('hotels'));
    }

    /**
     * Get dashboard data for a specific hotel
     */
    public function getDashboardData(Request $request)
    {
        $hotelId = $request->get('hotel_id');
        $dateRange = $request->get('date_range', 30);

        if (!$hotelId) {
            return response()->json(['error' => 'hotel_id es requerido'], 400);
        }

        try {
            // Calcular fechas
            $startDate = now()->subDays($dateRange)->format('Y-m-d');
            $endDate = now()->format('Y-m-d');

            // Obtener hotel
            $hotel = Hotel::find($hotelId);
            if (!$hotel) {
                return response()->json(['error' => 'Hotel no encontrado'], 404);
            }

            // Estadísticas del período actual
            $reviewStats = Review::where('hotel_id', $hotelId)
                ->whereBetween('scraped_at', [$startDate, $endDate])
                ->selectRaw('
                    COUNT(*) as total_reviews,
                    AVG(rating) as avg_rating,
                    COUNT(CASE WHEN rating >= 4 THEN 1 END) as positive_reviews,
                    COUNT(CASE WHEN rating <= 2 THEN 1 END) as negative_reviews,
                    COUNT(DISTINCT source_platform) as platforms_count
                ')
                ->first();

            // Estadísticas del período anterior para comparación
            $prevStartDate = now()->subDays($dateRange * 2)->format('Y-m-d');
            $prevEndDate = $startDate;

            $prevReviewStats = Review::where('hotel_id', $hotelId)
                ->whereBetween('scraped_at', [$prevStartDate, $prevEndDate])
                ->selectRaw('
                    COUNT(*) as total_reviews,
                    AVG(rating) as avg_rating
                ')
                ->first();

            // Calcular cambios porcentuales
            $reviewsChange = $prevReviewStats->total_reviews > 0 
                ? (($reviewStats->total_reviews - $prevReviewStats->total_reviews) / $prevReviewStats->total_reviews) * 100
                : 0;

            $ratingChange = $prevReviewStats->avg_rating > 0
                ? (($reviewStats->avg_rating - $prevReviewStats->avg_rating) / $prevReviewStats->avg_rating) * 100
                : 0;

            // Calcular IRO y índice semántico
            $iroScore = $this->calculateIRO($reviewStats);
            $semanticScore = $this->calculateSemanticIndex($hotelId, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => [
                    'hotel' => $hotel,
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'days' => $dateRange
                    ],
                    'iro' => [
                        'score' => $iroScore,
                        'change' => $reviewsChange > 0 ? $reviewsChange : -abs($reviewsChange),
                        'trend' => $reviewsChange >= 0 ? 'up' : 'down',
                        'calificacion' => [
                            'value' => round(($reviewStats->avg_rating / 5) * 100),
                            'trend' => $ratingChange >= 0 ? 'up' : 'down'
                        ],
                        'cobertura' => [
                            'value' => min(100, ($reviewStats->total_reviews / 100) * 100),
                            'trend' => $reviewsChange >= 0 ? 'up' : 'down'
                        ],
                        'reseñas' => [
                            'value' => min(100, $reviewStats->total_reviews * 2),
                            'trend' => $reviewsChange >= 0 ? 'up' : 'down'
                        ]
                    ],
                    'semantico' => $semanticScore,
                    'stats' => [
                        'total_reviews' => (int)$reviewStats->total_reviews,
                        'avg_rating' => round($reviewStats->avg_rating, 2),
                        'positive_reviews' => (int)$reviewStats->positive_reviews,
                        'negative_reviews' => (int)$reviewStats->negative_reviews,
                        'platforms_count' => (int)$reviewStats->platforms_count,
                        'changes' => [
                            'reviews' => round($reviewsChange, 1),
                            'rating' => round($ratingChange, 1)
                        ]
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error obteniendo datos del dashboard: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get OTAs data for a specific hotel
     */
    public function getOTAsData(Request $request)
    {
        $hotelId = $request->get('hotel_id');
        $dateRange = $request->get('date_range', 30);

        if (!$hotelId) {
            return response()->json(['error' => 'hotel_id es requerido'], 400);
        }

        try {
            $startDate = now()->subDays($dateRange)->format('Y-m-d');
            $endDate = now()->format('Y-m-d');

            // Estadísticas por plataforma para el período
            $otasData = Review::where('hotel_id', $hotelId)
                ->whereBetween('scraped_at', [$startDate, $endDate])
                ->selectRaw('
                    source_platform as platform,
                    COUNT(*) as reviews_count,
                    AVG(rating) as avg_rating,
                    MIN(scraped_at) as first_review,
                    MAX(scraped_at) as latest_review
                ')
                ->groupBy('source_platform')
                ->orderBy('reviews_count', 'desc')
                ->get();

            // Datos acumulados del año
            $yearStart = now()->startOfYear()->format('Y-m-d');
            $accumulatedData = Review::where('hotel_id', $hotelId)
                ->whereBetween('scraped_at', [$yearStart, $endDate])
                ->selectRaw('
                    source_platform as platform,
                    COUNT(*) as total_reviews,
                    AVG(rating) as avg_rating
                ')
                ->groupBy('source_platform')
                ->get()
                ->keyBy('platform');

            // Mapear datos con información de OTAs
            $otasMapping = [
                'booking' => ['name' => 'Booking.com', 'logo' => 'B', 'color' => 'bg-blue-700'],
                'google' => ['name' => 'Google', 'logo' => 'G', 'color' => 'bg-red-500'],
                'tripadvisor' => ['name' => 'TripAdvisor', 'logo' => 'T', 'color' => 'bg-green-600'],
                'expedia' => ['name' => 'Expedia Group', 'logo' => 'E', 'color' => 'bg-blue-600'],
                'despegar' => ['name' => 'Despegar Group', 'logo' => 'D', 'color' => 'bg-purple-600']
            ];

            $formattedOTAs = [];
            foreach ($otasMapping as $platform => $info) {
                $currentData = $otasData->firstWhere('platform', $platform);
                $accData = $accumulatedData->get($platform);

                $formattedOTAs[] = [
                    'platform' => $platform,
                    'name' => $info['name'],
                    'logo' => $info['logo'],
                    'bgColor' => $info['color'],
                    'rating' => $currentData ? round($currentData->avg_rating, 2) : null,
                    'reviews' => $currentData ? (int)$currentData->reviews_count : null,
                    'accumulated2025' => $accData ? round($accData->avg_rating, 2) : null,
                    'totalReviews' => $accData ? (int)$accData->total_reviews : 0
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $formattedOTAs
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error obteniendo datos de OTAs: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get reviews data for a specific hotel
     */
    public function getReviewsData(Request $request)
    {
        $hotelId = $request->get('hotel_id');
        $dateRange = $request->get('date_range', 30);
        $limit = $request->get('limit', 50);
        $offset = $request->get('offset', 0);

        if (!$hotelId) {
            return response()->json(['error' => 'hotel_id es requerido'], 400);
        }

        try {
            $startDate = now()->subDays($dateRange)->format('Y-m-d');
            $endDate = now()->format('Y-m-d');

            // Obtener reseñas
            $reviews = Review::where('hotel_id', $hotelId)
                ->whereBetween('scraped_at', [$startDate, $endDate])
                ->select([
                    'id',
                    'user_name as guest',
                    'user_location as country',
                    DB::raw("DATE_FORMAT(review_date, '%d %b %Y') as date"),
                    'traveler_type as tripType',
                    'platform_review_id as reviewId',
                    'source_platform as platform',
                    'rating',
                    'review_title as title',
                    'liked_text as positive',
                    'disliked_text as negative',
                    'property_response',
                    'scraped_at'
                ])
                ->orderBy('scraped_at', 'desc')
                ->limit($limit)
                ->offset($offset)
                ->get();

            // Procesar reseñas
            $reviews = $reviews->map(function ($review) {
                return [
                    'id' => $review->id,
                    'guest' => $review->guest ?: 'Usuario Anónimo',
                    'country' => $review->country ?: 'No especificado',
                    'date' => $review->date,
                    'tripType' => $review->tripType ?: 'No especificado',
                    'reviewId' => $review->reviewId,
                    'platform' => $review->platform,
                    'rating' => (float)$review->rating,
                    'title' => $review->title,
                    'positive' => $review->positive ?: '',
                    'negative' => $review->negative ?: '',
                    'hasResponse' => !empty($review->property_response)
                ];
            });

            // Total para paginación
            $total = Review::where('hotel_id', $hotelId)
                ->whereBetween('scraped_at', [$startDate, $endDate])
                ->count();

            return response()->json([
                'success' => true,
                'data' => $reviews,
                'pagination' => [
                    'total' => $total,
                    'limit' => (int)$limit,
                    'offset' => (int)$offset,
                    'hasMore' => ($offset + $limit) < $total
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error obteniendo reseñas: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get stats data for a specific hotel
     */
    public function getStatsData(Request $request)
    {
        $hotelId = $request->get('hotel_id');
        $dateRange = $request->get('date_range', 30);

        if (!$hotelId) {
            return response()->json(['error' => 'hotel_id es requerido'], 400);
        }

        try {
            $startDate = now()->subDays($dateRange)->format('Y-m-d');
            $endDate = now()->format('Y-m-d');

            $stats = Review::where('hotel_id', $hotelId)
                ->whereBetween('scraped_at', [$startDate, $endDate])
                ->selectRaw('
                    COUNT(*) as total_reviews,
                    AVG(rating) as avg_rating,
                    COUNT(CASE WHEN rating >= 4 THEN 1 END) as positive_count,
                    COUNT(CASE WHEN rating = 3 THEN 1 END) as neutral_count,
                    COUNT(CASE WHEN rating <= 2 THEN 1 END) as negative_count
                ')
                ->first();

            // Calcular NPS
            $totalReviews = (int)$stats->total_reviews;
            $promoters = (int)$stats->positive_count;
            $detractors = (int)$stats->negative_count;
            $nps = $totalReviews > 0 ? (($promoters - $detractors) / $totalReviews) * 100 : 0;

            // Cobertura por NPS
            $coverage = [
                'promoters' => $totalReviews > 0 ? round(($promoters / $totalReviews) * 100) : 0,
                'neutrals' => $totalReviews > 0 ? round(((int)$stats->neutral_count / $totalReviews) * 100) : 0,
                'detractors' => $totalReviews > 0 ? round(($detractors / $totalReviews) * 100) : 0
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'total_reviews' => $totalReviews,
                    'avg_rating' => round($stats->avg_rating, 2),
                    'coverage_total' => min(100, $totalReviews * 2), // Simulado
                    'nps' => round($nps),
                    'coverage_nps' => $coverage,
                    'cases_created' => 0 // Placeholder
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error obteniendo estadísticas: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Calculate IRO (Índice de Reputación Online)
     */
    private function calculateIRO($reviewStats)
    {
        if (!$reviewStats->total_reviews) return 0;

        $ratingScore = ($reviewStats->avg_rating / 5) * 40; // 40% peso
        $volumeScore = min(30, $reviewStats->total_reviews); // 30% peso máximo
        $sentimentScore = ($reviewStats->positive_reviews / $reviewStats->total_reviews) * 30; // 30% peso

        return round($ratingScore + $volumeScore + $sentimentScore);
    }

    /**
     * Calculate semantic index
     */
    private function calculateSemanticIndex($hotelId, $startDate, $endDate)
    {
        try {
            $sentimentData = Review::where('hotel_id', $hotelId)
                ->whereBetween('scraped_at', [$startDate, $endDate])
                ->selectRaw('
                    COUNT(*) as total_reviews,
                    COUNT(CASE WHEN 
                        LOWER(liked_text) REGEXP "malo|terrible|horrible|pesimo|sucio|roto|feo" OR
                        LOWER(disliked_text) REGEXP "malo|terrible|horrible|pesimo|sucio|roto|feo" OR
                        rating <= 2
                    THEN 1 END) as negative_mentions
                ')
                ->first();

            $totalReviews = (int)$sentimentData->total_reviews;
            $negativeMentions = (int)$sentimentData->negative_mentions;

            if ($totalReviews === 0) {
                return [
                    'score' => 50,
                    'status' => 'neutral',
                    'change' => 0,
                    'message' => 'No hay datos suficientes para calcular el índice semántico.'
                ];
            }

            $positivePercentage = (($totalReviews - $negativeMentions) / $totalReviews) * 100;

            $status = 'good';
            $message = 'Tu propiedad tiene un buen sentimiento general en las reseñas.';

            if ($positivePercentage < 30) {
                $status = 'bad';
                $message = 'Cuidado, tu propiedad tiene bastantes menciones negativas en los comentarios.';
            } elseif ($positivePercentage < 60) {
                $status = 'regular';
                $message = 'Tu propiedad tiene un sentimiento mixto en las reseñas.';
            }

            return [
                'score' => round($positivePercentage),
                'status' => $status,
                'change' => -50, // Placeholder
                'message' => $message
            ];

        } catch (\Exception $e) {
            return [
                'score' => 50,
                'status' => 'unknown',
                'change' => 0,
                'message' => 'Error calculando índice semántico.'
            ];
        }
    }
}