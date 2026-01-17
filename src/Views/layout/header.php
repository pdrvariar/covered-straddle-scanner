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

// Set page title if not defined
if (!isset($page_title)) {
    $page_title = 'Options Strategy';

    switch ($current_action) {
        case 'dashboard':
            $page_title = 'Dashboard - Options Strategy';
            break;
        case 'scan':
            $page_title = 'Scanner de Opções - Options Strategy';
            break;
        case 'results':
            $page_title = 'Resultados do Scanner - Options Strategy';
            break;
        case 'operations':
            $page_title = 'Operações - Options Strategy';
            break;
        case 'details':
            $page_title = 'Detalhes da Operação - Options Strategy';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>

    <!-- Meta Tags -->
    <meta name="description" content="Scanner avançado para operações de opções no mercado brasileiro">
    <meta name="author" content="Options Strategy">
    <meta name="keywords" content="opções, straddle, investimentos, bolsa, b3">
    <meta name="theme-color" content="#1a237e">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= $base_url ?>">
    <meta property="og:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta property="og:description" content="Scanner avançado para operações de opções no mercado brasileiro">
    <meta property="og:image" content="<?= $base_url ?>/img/og-image.jpg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= $base_url ?>">
    <meta property="twitter:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta property="twitter:description" content="Scanner avançado para operações de opções no mercado brasileiro">
    <meta property="twitter:image" content="<?= $base_url ?>/img/og-image.jpg">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Custom CSS -->
    <link href="<?= $base_url ?>/css/style.css?v=1.0.6" rel="stylesheet">
    <link href="<?= $base_url ?>/css/dashboard.css?v=1.0.7" rel="stylesheet">

    <!-- Additional CSS if any -->
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <style><?= $css ?></style>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= $base_url ?>/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= $base_url ?>/apple-touch-icon.png">

    <!-- Sistema de Notificações Global -->
    <script src="<?= $base_url ?>/js/notifications.js"></script>

    <!-- Header and Layout Styles -->
    <style>
        /* ===== BASE LAYOUT ===== */
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            overflow-x: hidden;
        }

        /* ===== HEADER STYLES ===== */
        .app-header {
            background: linear-gradient(135deg, #1a237e 0%, #283593 30%, #3949ab 100%);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(10px);
            height: 60px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .header-content {
            height: 100%;
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
            margin-right: 12px;
            background: linear-gradient(135deg, #64b5f6, #2196f3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 15px rgba(100, 181, 246, 0.3);
        }

        .logo-text {
            font-size: 1.8rem;
            font-weight: 800;
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

        /* Mobile Menu Button */
        .mobile-menu-btn {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: white;
            display: none;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .mobile-menu-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* ===== MAIN CONTAINER LAYOUT ===== */
        .app-container {
            display: flex;
            min-height: calc(100vh - 60px);
            margin-top: 60px;
            position: relative;
        }

        /* Sidebar Container */
        .sidebar-container {
            width: 250px;
            min-width: 250px;
            background: white;
            border-right: 1px solid rgba(0, 0, 0, 0.08);
            height: calc(100vh - 60px);
            position: sticky;
            top: 60px;
            overflow-y: auto;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.05);
            z-index: 100;
            transition: all 0.3s ease;
        }

        /* Main Content Area */
        .main-content {
            flex: 1;
            padding: 0.5rem 0.75rem;
            min-height: calc(100vh - 60px);
            overflow-y: auto;
            background: #f8fafc;
        }

        .content-card {
            background: white;
            border-radius: 20px;
            padding: 1rem;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            animation: slideIn 0.5s ease-out;
        }

        .content-card:hover {
            box-shadow: 0 8px 35px rgba(0, 0, 0, 0.12);
        }

        /* Page Header Modern */
        .page-header-modern {
            background: linear-gradient(135deg, #1a237e 0%, #3949ab 100%);
            padding: 1rem 1.25rem;
            border-radius: 20px;
            color: white;
            margin-bottom: 1rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(26, 35, 126, 0.2);
        }

        .page-header-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.1) 100%);
        }

        .page-header-modern h1,
        .page-header-modern h2 {
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            position: relative;
            font-weight: 700;
        }

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 1199px) {
            .sidebar-container {
                width: 260px;
                min-width: 260px;
            }

            .main-content {
                padding: 2rem;
            }
        }

        @media (max-width: 991px) {
            .mobile-menu-btn {
                display: flex;
            }

            .logo-tagline {
                display: none;
            }

            .sidebar-container {
                position: fixed;
                left: -280px;
                top: 60px;
                transition: left 0.3s ease;
                z-index: 1040;
            }

            .sidebar-container.show {
                left: 0;
            }

            .main-content {
                padding: 1.5rem;
                width: 100%;
            }

            .content-card {
                padding: 1.5rem;
                border-radius: 16px;
            }

            .page-header-modern {
                padding: 2rem 1.5rem;
            }
        }

        @media (max-width: 767px) {
            .header-content {
                padding: 0 1rem;
            }

            .main-content {
                padding: 1.25rem;
            }

            .content-card {
                padding: 1.25rem;
            }

            .page-header-modern {
                padding: 1.75rem 1.25rem;
                border-radius: 16px;
            }

            .logo-text {
                font-size: 1.5rem;
            }

            .logo-icon {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 575px) {
            .main-content {
                padding: 1rem;
            }

            .content-card {
                padding: 1rem;
                border-radius: 14px;
            }

            .page-header-modern {
                padding: 1.5rem 1rem;
                border-radius: 14px;
            }

            .logo-text {
                font-size: 1.3rem;
            }
        }

        /* ===== ANIMATIONS ===== */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .animate-slide-in {
            animation: slideIn 0.5s ease-out;
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        /* ===== SCROLLBAR STYLING ===== */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        .sidebar-container::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar-container::-webkit-scrollbar-track {
            background: #f8f9fa;
        }

        .sidebar-container::-webkit-scrollbar-thumb {
            background: #dee2e6;
            border-radius: 10px;
        }

        /* ===== PRELOADER ===== */
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
            z-index: 9998; /* Reduzido de 9999 para 9998 */
            opacity: 1;
            visibility: visible;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }

        .page-loader.hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none; /* Importante: não interfere com cliques */
        }

        .loader-content {
            text-align: center;
            color: white;
        }

        .loading-dots {
            display: inline-block;
            position: relative;
            width: 80px;
            height: 20px;
            margin-top: 1rem;
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

        /* ===== UTILITY CLASSES ===== */
        .glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .hover-lift {
            transition: all 0.3s ease;
        }

        .hover-lift:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }

        .transition-all {
            transition: all 0.3s ease;
        }

        /* ===== OVERLAY FOR MOBILE ===== */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 80px;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1039;
            backdrop-filter: blur(3px);
        }

        .sidebar-overlay.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        /* ===== MARKET STATUS BADGE ===== */
        .market-status-badge {
            background: rgba(76, 175, 80, 0.15);
            border: 1px solid rgba(76, 175, 80, 0.3);
            color: #4caf50;
            padding: 6px 12px;
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

        /* ===== USER PROFILE ===== */
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

        /* CORREÇÃO DO MAIN CONTENT */
        .main-content {
            flex: 1;
            padding: 0 !important; /* Remove padding que estava causando espaço */
            min-height: calc(100vh - 60px);
            overflow-y: auto;
            background: #f8fafc;
            margin: 0 !important; /* Garante que não há margem */
        }

        /* CORREÇÃO DO CONTENT-CARD */
        .content-card {
            background: white;
            border-radius: 0; /* Remove border-radius nas bordas que tocam as extremidades */
            padding: 1.5rem;
            box-shadow: none; /* Remove sombra que cria ilusão de espaço */
            border: none;
            margin: 0 !important;
            min-height: calc(100vh - 60px);
        }

        /* CORREÇÃO DO APP-CONTAINER */
        .app-container {
            display: flex;
            min-height: calc(100vh - 60px);
            margin-top: 60px;
            position: relative;
            gap: 0 !important; /* Remove qualquer gap entre sidebar e content */
        }

        /* CORREÇÃO DA SIDEBAR */
        .sidebar-container {
            width: 250px;
            min-width: 250px;
            background: white;
            border-right: 1px solid rgba(0, 0, 0, 0.08);
            height: calc(100vh - 60px);
            position: sticky;
            top: 60px;
            overflow-y: auto;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.05);
            z-index: 100;
            transition: all 0.3s ease;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* CORREÇÃO DOS WRAPPERS DE CONTEÚDO INTERNO */
        .content-wrapper,
        .py-4 {
            padding-top: 1.5rem !important;
            padding-left: 1.5rem !important;
            padding-right: 1.5rem !important;
            padding-bottom: 1.5rem !important;
        }

        /* CORREÇÃO DO PAGE HEADER */
        .page-header-gradient,
        .page-header-modern {
            margin-top: 0 !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            border-radius: 12px;
        }

        /* AJUSTE PARA TELAS MENORES */
        @media (max-width: 991px) {
            .main-content {
                padding: 0 !important;
                width: 100%;
            }

            .content-card {
                padding: 1rem;
            }

            .content-wrapper,
            .py-4 {
                padding: 1rem !important;
            }
        }

        @media (max-width: 767px) {
            .content-card {
                border-radius: 0;
                padding: 0.75rem;
            }

            .content-wrapper,
            .py-4 {
                padding: 0.75rem !important;
            }
        }

        /* CORREÇÃO ESPECÍFICA PARA O DASHBOARD */
        .pt-3 {
            padding-top: 0 !important;
        }

        /* GARANTIR QUE NÃO HÁ ESPAÇOS EXTRAS */
        .app-container > * {
            margin: 0 !important;
        }

        /* REMOVER PADDING DO BODY QUE PODE ESTAR INTERFERINDO */
        body.has-header-fixed {
            padding-top: 0 !important;
        }

        /* SIDEBAR SEM ESPAÇOS EXTRAS */
        .sidebar.glass {
            margin: 0 !important;
            padding-top: 1rem;
        }

        /* CORREÇÃO DO FOOTER PARA COLAR NO BOTTOM */
        .footer {
            margin-top: auto;
            padding: 1rem 0;
        }

        /* ===================================================================
           AUMENTAR LARGURA DO SIDEBAR
           Adicione ou substitua no header.php dentro da tag <style>
           ================================================================ */

        /* ===== SIDEBAR COM LARGURA AUMENTADA ===== */
        .sidebar-container {
            width: 280px;              /* Antes: 250px | Novo: 280px */
            min-width: 280px;          /* Antes: 250px | Novo: 280px */
            background: white;
            border-right: 1px solid rgba(0, 0, 0, 0.08);
            height: calc(100vh - 60px);
            position: sticky;
            top: 60px;
            overflow-y: auto;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.05);
            z-index: 100;
            transition: all 0.3s ease;
        }

        /* Ajustar padding interno para melhor aproveitamento */
        .sidebar-container .sidebar {
            padding: 1.5rem 1rem;
        }

        /* Ajustar itens do menu */
        .sidebar-container .nav-link {
            padding: 0.875rem 1rem !important;
            font-size: 0.95rem;
        }

        /* Ajustar ícones */
        .sidebar-container .nav-link i {
            width: 24px;
            font-size: 1.1rem;
        }

        /* Ajustar badges */
        .sidebar-container .badge {
            padding: 0.35em 0.65em;
            font-size: 0.8rem;
        }

        /* ===== RESPONSIVO ===== */
        @media (max-width: 1399px) {
            .sidebar-container {
                width: 270px;
                min-width: 270px;
            }
        }

        @media (max-width: 1199px) {
            .sidebar-container {
                width: 260px;
                min-width: 260px;
            }
        }

        @media (max-width: 991px) {
            /* Mobile - sidebar em offcanvas */
            .sidebar-container {
                width: 280px;
                min-width: 280px;
            }
        }

        /* ===== OPÇÕES DE LARGURA (ESCOLHA UMA) ===== */

        /* OPÇÃO 1: Sidebar Média (280px) - PADRÃO ACIMA */

        /* OPÇÃO 2: Sidebar Larga (320px) - Descomente para usar */
        /*
        .sidebar-container {
            width: 320px;
            min-width: 320px;
        }

        .sidebar-container .nav-link {
            padding: 1rem 1.25rem !important;
            font-size: 1rem;
        }

        .sidebar-container .nav-link i {
            width: 28px;
            font-size: 1.2rem;
        }
        */

        /* OPÇÃO 3: Sidebar Extra Larga (350px) - Descomente para usar */
        /*
        .sidebar-container {
            width: 350px;
            min-width: 350px;
        }

        .sidebar-container .sidebar {
            padding: 2rem 1.5rem;
        }

        .sidebar-container .nav-link {
            padding: 1rem 1.5rem !important;
            font-size: 1.05rem;
        }

        .sidebar-container .nav-link i {
            width: 30px;
            font-size: 1.3rem;
            margin-right: 1rem !important;
        }

        .sidebar-container .badge {
            padding: 0.4em 0.75em;
            font-size: 0.85rem;
        }

        .sidebar-container .sidebar-heading {
            font-size: 0.85rem;
            padding: 0.5rem 1.5rem;
        }
        */

        /* ===== MELHORIAS VISUAIS ADICIONAIS ===== */

        /* Melhorar espaçamento dos títulos de seção */
        .sidebar-heading {
            padding: 0.5rem 1rem;
            margin-bottom: 0.75rem;
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #6c757d;
        }

        /* Hover suave nos itens */
        .sidebar-container .nav-link {
            transition: all 0.3s ease;
            border-radius: 8px;
            margin-bottom: 0.25rem;
        }

        .sidebar-container .nav-link:hover {
            background: rgba(26, 35, 126, 0.08);
            transform: translateX(3px);
            padding-left: 1.25rem !important;
        }

        .sidebar-container .nav-link.active {
            background: linear-gradient(135deg, #1a237e 0%, #3949ab 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(26, 35, 126, 0.3);
        }

        .sidebar-container .nav-link.active i {
            color: white;
        }

        /* Melhorar divisores */
        .sidebar hr {
            margin: 1.5rem 1rem;
            opacity: 0.15;
            border-color: #1a237e;
        }

        /* Card de informações do mercado */
        .sidebar .bg-gradient {
            border-radius: 10px;
            padding: 1rem;
            margin: 0 1rem;
            transition: all 0.3s ease;
        }

        .sidebar .bg-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* Footer da sidebar */
        .sidebar .mt-auto {
            margin-top: auto;
            padding: 1rem;
        }

        /* Scrollbar customizada para a sidebar */
        .sidebar-container::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar-container::-webkit-scrollbar-track {
            background: #f8f9fa;
            border-radius: 10px;
        }

        .sidebar-container::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #1a237e, #3949ab);
            border-radius: 10px;
        }

        .sidebar-container::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #0d1642, #283593);
        }

        /* Animação de entrada */
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .sidebar-container {
            animation: slideInLeft 0.5s ease-out;
        }

        /* Badge pulsante para novidades */
        .badge.bg-success {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.8;
                transform: scale(1.05);
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
        <h5 class="mt-3">Carregando Options Strategy</h5>
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
            <div class="col-7 col-md-4">
                <div class="d-flex align-items-center">
                    <button class="mobile-menu-btn me-3" type="button" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>

                    <a href="/" class="brand-logo">
                        <div class="logo-icon">
                            <i class="fas fa-chart-network"></i>
                        </div>
                        <div>
                            <div class="logo-text">Options Strategy</div>
                            <div class="logo-tagline">Advanced Options Scanner</div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Market Status (Desktop only) -->
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
            <div class="col-5 col-md-4">
                <div class="d-flex align-items-center justify-content-end gap-2">
                    <!-- Header Actions -->
                    <div class="header-actions d-none d-md-flex">
                        <button class="header-btn" data-bs-toggle="tooltip" title="Notificações">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge">3</span>
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

                        <ul class="dropdown-menu dropdown-menu-end shadow-lg" style="min-width: 280px;">
                            <li class="dropdown-header text-center py-3" style="background: linear-gradient(135deg, #1a237e, #3949ab); color: white;">
                                <div class="user-avatar mx-auto mb-2">
                                    <i class="fas fa-user fa-lg"></i>
                                </div>
                                <h6 class="mt-2 mb-0"><?= htmlspecialchars($_SESSION['username'] ?? 'Usuário') ?></h6>
                                <small class="text-white-50">Trader Premium</small>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="#">
                                    <i class="fas fa-user text-primary me-3"></i>
                                    Meu Perfil
                                </a>
                                <a class="dropdown-item py-2" href="#">
                                    <i class="fas fa-cog text-primary me-3"></i>
                                    Configurações
                                </a>
                                <a class="dropdown-item py-2" href="#">
                                    <i class="fas fa-wallet text-primary me-3"></i>
                                    Minha Carteira
                                </a>
                                <a class="dropdown-item py-2" href="#">
                                    <i class="fas fa-history text-primary me-3"></i>
                                    Histórico
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item py-2" href="#">
                                    <i class="fas fa-question-circle text-primary me-3"></i>
                                    Ajuda & Suporte
                                </a>
                                <a class="dropdown-item py-2 text-danger" href="/?action=logout">
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

<!-- Overlay para Mobile Sidebar -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- Container Principal -->
<div class="app-container">
    <!-- Sidebar Desktop -->
    <div class="sidebar-container d-none d-lg-flex" id="desktopSidebar">
        <?php include __DIR__ . '/sidebar.php'; ?>
    </div>

    <!-- Conteúdo Principal -->
    <main class="main-content">
        <div class="content-card">
            <!-- O conteúdo de cada página será inserido aqui -->