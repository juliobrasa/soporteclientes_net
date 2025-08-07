<?php
session_start();
if (!isset($_SESSION['admin_logged'])) {
    header('Location: admin-login.php');
    exit;
}

include 'admin-config.php';

// Obtener estadísticas generales
function getAnalyticsStats() {
    $pdo = getDBConnection();
    if (!$pdo) return [];
    
    try {
        // Estadísticas básicas
        $stmt = $pdo->query("
            SELECT 
                COUNT(DISTINCT r.hotel_id) as hotels_with_reviews,
                COUNT(r.id) as total_reviews,
                AVG(r.normalized_rating) as avg_rating,
                COUNT(DISTINCT r.platform) as platforms_active
            FROM reviews r
        ");
        $basic = $stmt->fetch() ?: [];
        
        // Análisis de sentimientos
        $stmt = $pdo->query("
            SELECT 
                sentiment,
                COUNT(*) as count
            FROM review_analysis
            GROUP BY sentiment
        ");
        $sentiments = [];
        while ($row = $stmt->fetch()) {
            $sentiments[$row['sentiment']] = $row['count'];
        }
        
        // Reviews por plataforma
        $stmt = $pdo->query("
            SELECT 
                platform,
                COUNT(*) as count,
                AVG(normalized_rating) as avg_rating
            FROM reviews
            WHERE platform != 'unknown'
            GROUP BY platform
            ORDER BY count DESC
        ");
        $platforms = $stmt->fetchAll();
        
        // Tendencia por mes (últimos 6 meses)
        $stmt = $pdo->query("
            SELECT 
                DATE_FORMAT(scraped_at, '%Y-%m') as month,
                COUNT(*) as count
            FROM reviews
            WHERE scraped_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY month
            ORDER BY month ASC
        ");
        $trends = $stmt->fetchAll();
        
        return [
            'basic' => $basic,
            'sentiments' => $sentiments,
            'platforms' => $platforms,
            'trends' => $trends
        ];
        
    } catch (PDOException $e) {
        error_log("Error obteniendo analytics: " . $e->getMessage());
        return [];
    }
}

$stats = getAnalyticsStats();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Panel Admin Kavia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin-dashboard.php"><i class="fas fa-hotel"></i> Kavia Admin Panel</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="#"><i class="fas fa-user"></i> <?php echo $_SESSION['admin_email']; ?></a>
                <a class="nav-link" href="admin-logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="admin-dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin-hotels.php">
                                <i class="fas fa-building"></i> Hoteles
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin-ai.php">
                                <i class="fas fa-robot"></i> AI Providers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin-prompts.php">
                                <i class="fas fa-comments"></i> Prompts
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin-extraction.php">
                                <i class="fas fa-download"></i> Extracción
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin-apis.php">
                                <i class="fas fa-plug"></i> APIs Externas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin-tools.php">
                                <i class="fas fa-tools"></i> Herramientas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="admin-analytics.php">
                                <i class="fas fa-chart-bar"></i> Analytics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin-logs.php">
                                <i class="fas fa-file-alt"></i> Logs del Sistema
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-chart-bar"></i> Analytics de Reseñas</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-download"></i> Exportar Datos
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Reseñas</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['basic']['total_reviews'] ?? 0); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-comments fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Rating Promedio</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['basic']['avg_rating'] ?? 0, 2); ?>/5</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-star fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Hoteles con Reseñas</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['basic']['hotels_with_reviews'] ?? 0; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-building fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Plataformas Activas</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['basic']['platforms_active'] ?? 0; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-plug fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row">
                    <div class="col-xl-8 col-lg-7">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Tendencia de Reseñas (Últimos 6 Meses)</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="trendsChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-lg-5">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Análisis de Sentimientos</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="sentimentChart"></canvas>
                                <div class="mt-3">
                                    <div class="row">
                                        <div class="col-4 text-center">
                                            <span class="text-success"><i class="fas fa-smile"></i></span>
                                            <div class="small">Positivas</div>
                                            <div class="h6"><?php echo $stats['sentiments']['positive'] ?? 0; ?></div>
                                        </div>
                                        <div class="col-4 text-center">
                                            <span class="text-warning"><i class="fas fa-meh"></i></span>
                                            <div class="small">Neutrales</div>
                                            <div class="h6"><?php echo $stats['sentiments']['neutral'] ?? 0; ?></div>
                                        </div>
                                        <div class="col-4 text-center">
                                            <span class="text-danger"><i class="fas fa-frown"></i></span>
                                            <div class="small">Negativas</div>
                                            <div class="h6"><?php echo $stats['sentiments']['negative'] ?? 0; ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Platform Stats -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Reseñas por Plataforma</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Plataforma</th>
                                                <th>Total Reseñas</th>
                                                <th>Rating Promedio</th>
                                                <th>Porcentaje</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $totalReviews = $stats['basic']['total_reviews'] ?? 1;
                                            foreach ($stats['platforms'] as $platform): 
                                                $percentage = round(($platform['count'] / $totalReviews) * 100, 1);
                                            ?>
                                            <tr>
                                                <td>
                                                    <i class="fas fa-circle text-primary"></i>
                                                    <?php echo ucfirst($platform['platform']); ?>
                                                </td>
                                                <td><?php echo number_format($platform['count']); ?></td>
                                                <td><?php echo number_format($platform['avg_rating'], 2); ?>/5</td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar" style="width: <?php echo $percentage; ?>%">
                                                            <?php echo $percentage; ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Configurar Chart.js
    Chart.defaults.font.family = 'Nunito Sans';
    Chart.defaults.font.size = 12;
    Chart.defaults.color = '#858796';

    // Gráfico de tendencias
    const trendsData = <?php echo json_encode($stats['trends']); ?>;
    const trendsLabels = trendsData.map(item => item.month);
    const trendsValues = trendsData.map(item => item.count);

    const trendsCtx = document.getElementById('trendsChart').getContext('2d');
    new Chart(trendsCtx, {
        type: 'line',
        data: {
            labels: trendsLabels,
            datasets: [{
                label: 'Reseñas',
                data: trendsValues,
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Gráfico de sentimientos
    const sentimentData = <?php echo json_encode($stats['sentiments']); ?>;
    const sentimentCtx = document.getElementById('sentimentChart').getContext('2d');
    new Chart(sentimentCtx, {
        type: 'doughnut',
        data: {
            labels: ['Positivas', 'Neutrales', 'Negativas'],
            datasets: [{
                data: [
                    sentimentData.positive || 0,
                    sentimentData.neutral || 0,
                    sentimentData.negative || 0
                ],
                backgroundColor: ['#1cc88a', '#f6c23e', '#e74a3b']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    </script>
</body>
</html>