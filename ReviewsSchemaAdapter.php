<?php
/**
 * Adapter para normalizar operaciones con diferentes esquemas de reviews
 */

class ReviewsSchemaAdapter 
{
    private $pdo;
    private $tableName;

    public function __construct($pdo, $tableName = 'reviews_unified_final') {
        $this->pdo = $pdo;
        $this->tableName = $tableName;
    }

    /**
     * Insertar review normalizando campos
     */
    public function insertReview($data) {
        // Normalizar campos
        $normalized = $this->normalizeReviewData($data);

        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO {$this->tableName} (
                unique_id, hotel_id, user_name, review_text, 
                liked_text, disliked_text, rating, source_platform,
                property_response, review_date, scraped_at,
                platform_review_id, extraction_run_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $normalized['unique_id'],
            $normalized['hotel_id'],
            $normalized['user_name'],
            $normalized['review_text'],
            $normalized['liked_text'],
            $normalized['disliked_text'],
            $normalized['rating'],
            $normalized['source_platform'],
            $normalized['property_response'],
            $normalized['review_date'],
            $normalized['scraped_at'],
            $normalized['platform_review_id'],
            $normalized['extraction_run_id']
        ]);
    }

    /**
     * Normalizar datos de review desde diferentes fuentes
     */
    private function normalizeReviewData($data) {
        return [
            'unique_id' => $data['unique_id'] ?? $data['id'] ?? uniqid('rev_'),
            'hotel_id' => $data['hotel_id'],
            'user_name' => $data['user_name'] ?? $data['reviewer_name'] ?? $data['authorName'] ?? 'Anónimo',
            'review_text' => $data['review_text'] ?? $data['comment'] ?? null,
            'liked_text' => $data['liked_text'] ?? null,
            'disliked_text' => $data['disliked_text'] ?? null,
            'rating' => $data['rating'] ?? $data['normalized_rating'] ?? 0,
            'source_platform' => $data['source_platform'] ?? $data['platform'] ?? 'unknown',
            'property_response' => $data['property_response'] ?? $data['response_from_owner'] ?? null,
            'review_date' => $data['review_date'] ?? $data['date_created'] ?? null,
            'scraped_at' => $data['scraped_at'] ?? date('Y-m-d H:i:s'),
            'platform_review_id' => $data['platform_review_id'] ?? $data['reviewId'] ?? $data['external_id'] ?? null,
            'extraction_run_id' => $data['extraction_run_id'] ?? null
        ];
    }
}
