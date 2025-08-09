<?php
/**
 * Migración de datos existentes al esquema unificado
 */

require_once 'env-loader.php';
require_once 'ReviewsSchemaAdapter.php';

class ReviewsDataMigrator {
    private $pdo;
    private $adapter;

    public function migrate() {
        // Migrar desde tabla 'reviews' existente
        // Migrar desde tabla 'reviews_unified' si existe
        // Verificar integridad de datos
    }
}
