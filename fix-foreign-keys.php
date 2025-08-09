<?php
require_once 'env-loader.php';

try {
    $pdo = createDatabaseConnection();
    
    echo "🔧 FIXING FOREIGN KEY CONSTRAINTS\n";
    echo str_repeat("=", 50) . "\n\n";
    
    // 1. Check extraction_jobs table structure
    echo "📊 1. Checking extraction_jobs table structure...\n";
    $stmt = $pdo->query("DESCRIBE extraction_jobs");
    $extractionJobsColumns = array_column($stmt->fetchAll(), 'Field');
    echo "  ✅ extraction_jobs columns: " . implode(', ', $extractionJobsColumns) . "\n\n";
    
    // 2. Check apify_extraction_runs table structure
    echo "📊 2. Checking apify_extraction_runs table structure...\n";
    $stmt = $pdo->query("DESCRIBE apify_extraction_runs");
    $apifyRunsColumns = array_column($stmt->fetchAll(), 'Field');
    echo "  ✅ apify_extraction_runs columns: " . implode(', ', $apifyRunsColumns) . "\n\n";
    
    // 3. Add job_id column without foreign key first
    echo "🔧 3. Adding job_id column to apify_extraction_runs...\n";
    if (!in_array('job_id', $apifyRunsColumns)) {
        try {
            $pdo->exec("ALTER TABLE apify_extraction_runs ADD COLUMN job_id INT");
            echo "  ✅ Added job_id column\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                echo "  ⚠️  Error adding job_id: " . $e->getMessage() . "\n";
            } else {
                echo "  ℹ️  job_id column already exists\n";
            }
        }
    } else {
        echo "  ℹ️  job_id column already exists\n";
    }
    
    // 4. Add started_at and finished_at if missing
    echo "🔧 4. Adding timestamp columns...\n";
    $timestampColumns = ['started_at', 'finished_at'];
    foreach ($timestampColumns as $col) {
        if (!in_array($col, $apifyRunsColumns)) {
            try {
                $pdo->exec("ALTER TABLE apify_extraction_runs ADD COLUMN $col TIMESTAMP NULL");
                echo "  ✅ Added $col column\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate column') === false) {
                    echo "  ⚠️  Error adding $col: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    // 5. Create indexes for performance
    echo "🔧 5. Creating performance indexes...\n";
    $indexes = [
        "CREATE INDEX idx_apify_job_id ON apify_extraction_runs (job_id)",
        "CREATE INDEX idx_apify_status ON apify_extraction_runs (status)",
        "CREATE INDEX idx_apify_started_at ON apify_extraction_runs (started_at)"
    ];
    
    foreach ($indexes as $indexQuery) {
        try {
            $pdo->exec($indexQuery);
            echo "  ✅ " . substr($indexQuery, 0, 50) . "...\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key') === false) {
                echo "  ⚠️  Index error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // 6. Update existing records with started_at from created_at if available
    echo "🔧 6. Updating existing records...\n";
    try {
        $result = $pdo->exec("UPDATE apify_extraction_runs SET started_at = created_at WHERE started_at IS NULL AND created_at IS NOT NULL");
        echo "  ✅ Updated $result records with started_at\n";
    } catch (PDOException $e) {
        echo "  ℹ️  Could not update started_at: " . $e->getMessage() . "\n";
    }
    
    echo "\n✅ FOREIGN KEY FIXES COMPLETED\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>