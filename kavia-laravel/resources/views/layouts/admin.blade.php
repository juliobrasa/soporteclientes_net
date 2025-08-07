<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel') - Kavia Hotels</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #8b5cf6;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
        }

        body {
            background-color: var(--light-color);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .sidebar {
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            margin: 0.25rem 0.5rem;
            transition: all 0.2s ease;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }

        .main-content {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            margin: 1rem;
            padding: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary-color);
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .table-responsive {
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.375rem 0.75rem;
        }

        .btn {
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .navbar-brand {
            font-weight: bold;
            color: var(--primary-color) !important;
        }

        .alert {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .modal-content {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .form-control, .form-select {
            border-radius: 0.5rem;
            border: 1px solid #d1d5db;
            transition: all 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
        }

        .loading-spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid #f3f3f3;
            border-top: 2px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    @stack('styles')
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white mb-0">
                            <i class="fas fa-hotel"></i> Kavia Admin
                        </h4>
                        <small class="text-white-50">Panel de Administración</small>
                    </div>

                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" 
                               href="{{ route('admin.dashboard') }}">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.hotels*') ? 'active' : '' }}" 
                               href="{{ route('admin.hotels') }}">
                                <i class="fas fa-hotel me-2"></i>
                                Hoteles
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.ai-providers*') ? 'active' : '' }}" 
                               href="{{ route('admin.ai-providers') }}">
                                <i class="fas fa-robot me-2"></i>
                                Proveedores IA
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.prompts*') ? 'active' : '' }}" 
                               href="{{ route('admin.prompts') }}">
                                <i class="fas fa-file-alt me-2"></i>
                                Prompts
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.external-apis*') ? 'active' : '' }}" 
                               href="{{ route('admin.external-apis') }}">
                                <i class="fas fa-plug me-2"></i>
                                APIs Externas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.extraction-jobs*') ? 'active' : '' }}" 
                               href="{{ route('admin.extraction-jobs') }}">
                                <i class="fas fa-download me-2"></i>
                                Extracción
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.system-logs*') ? 'active' : '' }}" 
                               href="{{ route('admin.system-logs') }}">
                                <i class="fas fa-chart-line me-2"></i>
                                Logs del Sistema
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.tools*') ? 'active' : '' }}" 
                               href="{{ route('admin.tools') }}">
                                <i class="fas fa-tools me-2"></i>
                                Herramientas
                            </a>
                        </li>
                    </ul>

                    <hr class="my-3" style="border-color: rgba(255,255,255,0.2);">

                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="nav-link text-white-50 border-0 bg-transparent">
                                    <i class="fas fa-sign-out-alt me-2"></i>
                                    Cerrar Sesión
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">@yield('page-title', 'Dashboard')</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <small class="text-muted">
                                <i class="fas fa-user me-1"></i>
                                {{ Auth::user()->name }}
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Alerts -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Content -->
                <div class="main-content">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        // CSRF Token para AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Inicializar DataTables por defecto
        $(document).ready(function() {
            $('.data-table').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
                }
            });
        });
    </script>

    @stack('scripts')
</body>
</html>