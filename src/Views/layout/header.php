<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default timezone
date_default_timezone_set('America/Sao_Paulo');

// Base URL
$base_url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$base_url .= "://" . $_SERVER['HTTP_HOST'];

// Determine active page for sidebar
$current_action = $_GET['action'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'Covered Straddle Scanner') ?></title>

    <!-- Meta Tags -->
    <meta name="description" content="Scanner avançado para operações de Covered Straddle no mercado brasileiro">
    <meta name="author" content="Covered Straddle Scanner">
    <meta name="keywords" content="opções, straddle, investimentos, bolsa, b3">
    <meta name="theme-color" content="#1f77b4">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= $base_url ?>">
    <meta property="og:title" content="<?= htmlspecialchars($page_title ?? 'Covered Straddle Scanner') ?>">
    <meta property="og:description" content="Scanner avançado para operações de Covered Straddle no mercado brasileiro">
    <meta property="og:image" content="<?= $base_url ?>/img/og-image.jpg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= $base_url ?>">
    <meta property="twitter:title" content="<?= htmlspecialchars($page_title ?? 'Covered Straddle Scanner') ?>">
    <meta property="twitter:description" content="Scanner avançado para operações de Covered Straddle no mercado brasileiro">
    <meta property="twitter:image" content="<?= $base_url ?>/img/og-image.jpg">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Custom CSS -->
    <link href="<?= $base_url ?>/css/style.css" rel="stylesheet">

    <!-- Dashboard CSS -->
    <link href="<?= $base_url ?>/css/dashboard.css" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= $base_url ?>/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= $base_url ?>/apple-touch-icon.png">

    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link href="<?= $css ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Sistema de Notificações Global -->
    <script src="<?= $base_url ?>/js/notifications.js"></script>

    <?php
    // Renderizar notificações flash se existirem
    if (function_exists('render_flash_notifications')) {
        echo render_flash_notifications();
    }
    ?>

    <!-- Header and Layout Styles -->
    <style>
        /* Layout Structure Fix */
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .main-wrapper {
            display: flex;
            min-height: calc(100vh - 120px);
            flex: 1;
        }

        /* Enhanced Header */
        .app-header {
            background: linear-gradient(135deg, #1a237e 0%, #283593 30%, #3949ab 100%);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(10px);
            height: 80px;
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 1030;
        }

        .header-content {
            height: 80px;
            padding: 0 1.5rem;
        }

        /* Brand Logo */
        .brand-logo {
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .brand-logo:hover {
            transform: translateY(-2px);
        }

        .logo-icon {
            font-size: 2.2rem;
            color: #64b5f6;
            margin-right: 12px;
            text-shadow: 0 0 15px rgba(100, 181, 246, 0.3);
            background: linear-gradient(135deg, #2196f3, #1976d2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .logo-text {
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #ffffff, #bbdefb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .logo-tagline {
            font-size: 0.75rem;
            color: #bbdefb;
            letter-spacing: 0.5px;
            margin-left: 12px;
            padding-left: 12px;
            border-left: 1px solid rgba(187, 222, 251, 0.3);
        }

        /* User Profile */
        .user-profile {
            position: relative;
        }

        .user-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2196f3, #1976d2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            border: 3px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.2);
        }

        .user-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(33, 150, 243, 0.3);
        }

        .user-info {
            margin-right: 12px;
        }

        .user-name {
            font-size: 0.95rem;
            font-weight: 600;
            color: white;
            margin-bottom: 2px;
        }

        .user-role {
            font-size: 0.75rem;
            color: #bbdefb;
        }

        /* Header Actions */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-btn {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            position: relative;
        }

        .header-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .header-btn i {
            font-size: 1.2rem;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: linear-gradient(135deg, #ff4081, #f50057);
            color: white;
            font-size: 0.7rem;
            min-width: 20px;
            height: 20px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #1a237e;
        }

        /* Dropdown Menu */
        .user-dropdown-menu {
            min-width: 280px;
            border-radius: 16px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .dropdown-header {
            background: linear-gradient(135deg, #1a237e, #3949ab);
            color: white;
            padding: 1.5rem;
        }

        .dropdown-avatar {
            width: 60px;
            height: 60px;
            margin-bottom: 12px;
        }

        .dropdown-body {
            padding: 1rem 0;
        }

        .dropdown-item {
            padding: 0.75rem 1.5rem;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            padding-left: 2rem;
        }

        /* Mobile Menu Button */
        .mobile-menu-btn {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: white;
            display: none;
        }

        @media (max-width: 992px) {
            .mobile-menu-btn {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .user-info {
                display: none;
            }

            .logo-tagline {
                display: none;
            }

            .header-content {
                padding: 0 1rem;
            }
        }

        /* Market Status Badge */
        .market-status-badge {
            background: rgba(76, 175, 80, 0.15);
            border: 1px solid rgba(76, 175, 80, 0.3);
            color: #4caf50;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .market-status-badge i {
            font-size: 0.7rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        /* Preloader Styles (mantido) */
        .page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #1a237e 0%, #3949ab 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 1;
            visibility: visible;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }

        .page-loader.hidden {
            opacity: 0;
            visibility: hidden;
        }

        .loader-content {
            text-align: center;
            color: white;
            animation: fadeIn 0.5s ease;
        }

        /* Loading dots animation (mantido) */
        .loading-dots {
            display: inline-block;
            position: relative;
            width: 80px;
            height: 20px;
        }

        .loading-dots div {
            position: absolute;
            width: 13px;
            height: 13px;
            border-radius: 50%;
            background: white;
            animation-timing-function: cubic-bezier(0, 1, 1, 0);
        }

        .loading-dots div:nth-child(1) {
            left: 8px;
            animation: loading-dots1 0.6s infinite;
        }

        .loading-dots div:nth-child(2) {
            left: 8px;
            animation: loading-dots2 0.6s infinite;
        }

        .loading-dots div:nth-child(3) {
            left: 32px;
            animation: loading-dots2 0.6s infinite;
        }

        .loading-dots div:nth-child(4) {
            left: 56px;
            animation: loading-dots3 0.6s infinite;
        }

        @keyframes loading-dots1 {
            0% { transform: scale(0); }
            100% { transform: scale(1); }
        }

        @keyframes loading-dots3 {
            0% { transform: scale(1); }
            100% { transform: scale(0); }
        }

        @keyframes loading-dots2 {
            0% { transform: translate(0, 0); }
            100% { transform: translate(24px, 0); }
        }

        /* Content Area Spacing */
        .content-wrapper {
            margin-top: 20px;
            padding-top: 20px;
        }

        .sidebar {
            background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%);
            border-right: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 16px;
            margin: 10px;
            min-height: calc(100vh - 140px);
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .sidebar:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }

        .sidebar .nav-link {
            color: #495057;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            margin: 2px 0;
        }

        .sidebar .nav-link:hover {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            color: #1976d2;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background: linear-gradient(135deg, #1976d2 0%, #2196f3 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
        }

        .sidebar .nav-link.active i {
            color: white;
        }

        .sidebar .nav-link i {
            width: 24px;
            text-align: center;
            color: #6c757d;
        }

        .sidebar .sidebar-heading {
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .sidebar .badge {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
        }

        /* Ajustes de espaçamento */
        .content-wrapper {
            margin-top: 0;
            padding-top: 0;
        }

        .main-wrapper {
            gap: 20px;
            align-items: flex-start;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                margin: 0;
                border-radius: 0;
                border-right: none;
                min-height: auto;
            }

            .main-wrapper {
                flex-direction: column;
            }
        }
    </style>
</head>
<body class="has-header-fixed">
<!-- Preloader -->
<div class="page-loader" id="pageLoader">
    <div class="loader-content">
        <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <h5 class="mt-3">Carregando Covered Straddle Scanner</h5>
        <div class="loading-dots mt-2">
            <div></div>
            <div></div>
            <div></div>
            <div></div>
        </div>
    </div>
</div>

<!-- Header -->
<header class="app-header fixed-top">
    <div class="container-fluid header-content">
        <div class="row align-items-center h-100">
            <!-- Logo and Brand -->
            <div class="col-md-3">
                <div class="d-flex align-items-center">
                    <button class="mobile-menu-btn me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
                        <i class="fas fa-bars"></i>
                    </button>

                    <a href="/" class="brand-logo">
                        <div class="logo-icon">
                            <i class="fas fa-chart-network"></i>
                        </div>
                        <div>
                            <div class="logo-text">Covered Straddle</div>
                            <div class="logo-tagline">Advanced Options Scanner</div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Market Status -->
            <div class="col-md-4 d-none d-md-block">
                <div class="d-flex align-items-center justify-content-center h-100">
                    <div class="d-flex gap-3">
                        <div class="market-status-badge">
                            <i class="fas fa-circle"></i>
                            <span>Mercado Aberto</span>
                        </div>
                        <div class="text-white small">
                            <i class="fas fa-clock me-1"></i>
                            <?= date('H:i') ?> BRT
                        </div>
                    </div>
                </div>
            </div>

            <!-- User and Actions -->
            <div class="col-md-5">
                <div class="d-flex align-items-center justify-content-end gap-3">
                    <!-- Header Actions -->
                    <div class="header-actions">
                        <button class="header-btn" data-bs-toggle="tooltip" title="Notificações">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge">3</span>
                        </button>

                        <button class="header-btn" data-bs-toggle="tooltip" title="Mensagens">
                            <i class="fas fa-envelope"></i>
                        </button>

                        <button class="header-btn" data-bs-toggle="tooltip" title="Configurações">
                            <i class="fas fa-cog"></i>
                        </button>

                        <button class="header-btn" data-bs-toggle="tooltip" title="Relatórios">
                            <i class="fas fa-chart-bar"></i>
                        </button>
                    </div>

                    <!-- User Profile -->
                    <div class="user-profile dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="user-info text-end me-2 d-none d-md-block">
                                <div class="user-name"><?= htmlspecialchars($_SESSION['username'] ?? 'Usuário') ?></div>
                                <div class="user-role">Trader Premium</div>
                            </div>
                            <div class="user-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end user-dropdown-menu shadow-lg">
                            <li class="dropdown-header text-center">
                                <div class="user-avatar mx-auto">
                                    <i class="fas fa-user fa-lg"></i>
                                </div>
                                <h6 class="mt-2 mb-0"><?= htmlspecialchars($_SESSION['username'] ?? 'Usuário') ?></h6>
                                <small class="text-white-50">Trader Premium</small>
                            </li>

                            <li class="dropdown-body">
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-user text-primary me-3"></i>
                                    Meu Perfil
                                </a>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-cog text-primary me-3"></i>
                                    Configurações
                                </a>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-wallet text-primary me-3"></i>
                                    Minha Carteira
                                </a>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-history text-primary me-3"></i>
                                    Histórico
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-question-circle text-primary me-3"></i>
                                    Ajuda & Suporte
                                </a>
                                <a class="dropdown-item text-danger" href="/?action=logout">
                                    <i class="fas fa-sign-out-alt me-3"></i>
                                    Sair
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Main Container -->
<div class="container-fluid main-wrapper mt-5 pt-4">
    <!-- Mobile Sidebar Overlay -->
    <div class="offcanvas offcanvas-start d-md-none" tabindex="-1" id="sidebarMenu" style="max-width: 320px;">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title">Menu</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <?php include __DIR__ . '/sidebar.php'; ?>
        </div>
    </div>

    <!-- Desktop Sidebar - AUMENTADO para col-lg-3 -->
    <div class="d-none d-md-block col-md-4 col-lg-3 px-3">
        <?php include __DIR__ . '/sidebar.php'; ?>
    </div>

    <!-- Main Content Area - AJUSTADO proporção -->
    <main class="col-md-8 col-lg-9 px-md-4 content-wrapper">
        <div class="container-fluid py-3">