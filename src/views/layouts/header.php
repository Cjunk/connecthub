<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME; ?></title>
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='32' height='32' viewBox='0 0 32 32'%3E%3Ccircle cx='16' cy='16' r='16' fill='%23ffc107'/%3E%3Ctext x='16' y='22' text-anchor='middle' font-family='sans-serif' font-size='18' fill='white'%3EC%3C/text%3E%3C/svg%3E">
    <link rel="shortcut icon" href="data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='32' height='32' viewBox='0 0 32 32'%3E%3Ccircle cx='16' cy='16' r='16' fill='%23ffc107'/%3E%3Ctext x='16' y='22' text-anchor='middle' font-family='sans-serif' font-size='18' fill='white'%3EC%3C/text%3E%3C/svg%3E">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- Performance Optimizations -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" crossorigin="anonymous">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" crossorigin="anonymous"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <!-- Custom CSS -->
    <?php if (file_exists(ROOT_PATH . '/assets/css/clean-style.css')): ?>
        <link href="assets/css/clean-style.css" rel="stylesheet">
    <?php endif; ?>
    
    <!-- Ensure background texture loads -->
    <style>
        /* ==================== COLOR PALETTE VARIABLES ==================== */
        :root {
            /* Primary Brand Colors - Updated with green theme */
            --brand-primary-dark: #1e5f2a;
            --brand-primary-medium: #2d8f3f;
            --brand-primary-light: #4db365;
            --brand-success: #28a745;
            --brand-success-light: #e8f5e9;
            
            /* Background Colors */
            --bg-light-primary: #f8f9fa;
            --bg-light-secondary: #e8f5e8;
            --bg-light-tertiary: #e9ecef;
            
            /* Accent Colors */
            --accent-green: #90EE90;
            --accent-blue: #87CEEB;
            --accent-yellow: #FFD700;
            
            /* Text Colors */
            --text-white: rgba(255, 255, 255, 0.95);
            --text-white-hover: rgba(255, 255, 255, 1);
            --text-dark: #2c3e50;
            --text-blue: #1e40af;
            
            /* Glass Effect Colors */
            --glass-white: rgba(255, 255, 255, 0.95);
            --glass-border: rgba(255, 255, 255, 0.3);
            --glass-border-dark: rgba(0, 0, 0, 0.15);
            
            /* Shadow Colors - Softer shadows */
            --shadow-light: rgba(0, 0, 0, 0.05);
            --shadow-medium: rgba(0, 0, 0, 0.1);
            --shadow-dark: rgba(0, 0, 0, 0.15);
            
            /* Interactive Colors */
            --hover-overlay: rgba(255, 255, 255, 0.1);
            --hover-overlay-strong: rgba(255, 255, 255, 0.2);
            --dropdown-hover: linear-gradient(135deg, rgba(74, 144, 226, 0.1), rgba(144, 238, 144, 0.1));
            
            /* Card styling */
            --card-border-radius: 12px;
            --card-border: rgba(0, 0, 0, 0.05);
        }
        
        /* ==================== PERFORMANCE OPTIMIZATIONS ==================== */
        /* Performance Optimizations */
        * {
            box-sizing: border-box;
        }
        
        /* Respect user preferences for reduced motion */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
        
        /* ==================== BACKGROUND SYSTEM ==================== */
        /* Enhanced Background System with Performance Optimization */
        body {
            background: linear-gradient(135deg, var(--bg-light-primary) 0%, var(--bg-light-secondary) 30%, var(--bg-light-tertiary) 100%) !important;
            background-attachment: fixed !important;
            position: relative;
            min-height: 100vh;
            will-change: auto !important;
            padding-top: 80px !important; /* Account for taller navbar */
        }
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                /* Subtle grid pattern for definition */
                linear-gradient(rgba(0,123,255,0.01) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,123,255,0.01) 1px, transparent 1px),
                /* Diagonal lines for texture */
                repeating-linear-gradient(45deg, transparent, transparent 35px, rgba(120,119,198,0.008) 35px, rgba(120,119,198,0.008) 70px),
                /* Original radial gradients with green touches */
                radial-gradient(circle at 20% 50%, rgba(120, 119, 198, 0.04) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 99, 132, 0.04) 0%, transparent 50%),
                radial-gradient(circle at 40% 80%, rgba(54, 162, 235, 0.04) 0%, transparent 50%),
                radial-gradient(circle at 60% 30%, rgba(40, 167, 69, 0.03) 0%, transparent 60%),
                radial-gradient(circle at 10% 80%, rgba(32, 201, 151, 0.025) 0%, transparent 55%);
            background-size: 
                60px 60px,
                60px 60px,
                100px 100px,
                400px 400px, 
                300px 300px, 
                500px 500px,
                350px 350px,
                450px 450px;
            background-position: 0 0, 0 0, 0 0, 0 0, 100px 100px, 200px 200px, 150px 50px, 250px 300px;
            animation: subtleMove 30s ease-in-out infinite;
            pointer-events: none;
            z-index: -999;
        }
        @keyframes subtleMove {
            0%, 100% { 
                background-position: 0 0, 0 0, 0 0, 0 0, 100px 100px, 200px 200px, 150px 50px, 250px 300px; 
            }
            50% { 
                background-position: 30px 30px, -30px 30px, 50px 50px, 50px 50px, 150px 150px, 250px 250px, 200px 100px, 300px 350px; 
            }
        }
        /* Enhanced card definition - ensure content is visible */
        .card {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(12px) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12) !important;
            position: relative;
            z-index: 1;
        }
        /* Ensure all content is above background */
        .container, main {
            position: relative;
            z-index: 2;
        }
        /* Navbar and dropdown z-index */
        .navbar {
            z-index: 1050 !important;
        }
        .dropdown-menu {
            z-index: 1060 !important;
            position: absolute !important;
            display: block !important;
            opacity: 0 !important;
            visibility: hidden !important;
            transition: opacity 0.15s linear !important;
        }
        .dropdown-menu.show {
            opacity: 1 !important;
            visibility: visible !important;
        }
        .navbar-nav .dropdown:hover .dropdown-menu {
            opacity: 1 !important;
            visibility: visible !important;
        }
        /* Fix main content top spacing */
        main {
            margin-top: 0 !important;
            padding-top: 0 !important; /* Let body handle the top spacing */
        }
        
        /* Enhanced Navigation Menu Items */
        .navbar-nav .nav-link {
            color: var(--text-dark) !important;
            text-shadow: 
                1px 1px 2px rgba(255, 255, 255, 0.8) !important;
            font-weight: 500 !important;
            letter-spacing: 0.3px !important;
            transition: all 0.3s ease !important;
            position: relative !important;
        }
        
        .navbar-nav .nav-link:hover {
            color: var(--text-blue) !important;
            text-shadow: 
                2px 2px 4px rgba(255, 255, 255, 0.9) !important;
            transform: translateY(-1px) !important;
            background: rgba(0, 0, 0, 0.05) !important;
            border-radius: 4px !important;
        }
        
        .navbar-nav .nav-link:focus,
        .navbar-nav .nav-link.active {
            color: rgba(135, 206, 250, 1) !important;
            text-shadow: 
                1px 1px 3px rgba(0, 0, 0, 0.6),
                0 0 8px rgba(135, 206, 250, 0.5) !important;
        }
        
        /* Enhanced Dark Mode Toggle Button */
        #darkModeToggle {
            color: var(--text-dark) !important;
            text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.8) !important;
            transition: all 0.3s ease !important;
        }
        
        #darkModeToggle:hover {
            color: var(--accent-yellow) !important;
            text-shadow: 
                2px 2px 4px rgba(0, 0, 0, 0.3),
                0 0 8px rgba(255, 223, 0, 0.4) !important;
            transform: scale(1.1) translateY(-1px) !important;
        }
        
        /* Enhanced User Dropdown */
        .navbar-nav .dropdown-toggle {
            color: var(--text-dark) !important;
            text-shadow: 
                1px 1px 2px rgba(255, 255, 255, 0.8) !important;
            font-weight: 500 !important;
            transition: all 0.3s ease !important;
            padding: 8px 16px !important;
            border-radius: 25px !important;
            background: rgba(0, 0, 0, 0.05) !important;
            border: 1px solid rgba(0, 0, 0, 0.1) !important;
            backdrop-filter: blur(10px) !important;
        }
        
        .navbar-nav .dropdown {
            position: relative !important;
            z-index: 9999 !important;
        }
        
        .navbar-nav .dropdown-toggle:hover {
            color: var(--text-white-hover) !important;
            text-shadow: 
                2px 2px 4px rgba(0, 0, 0, 0.5),
                0 0 8px rgba(255, 255, 255, 0.3),
                0 0 12px rgba(144, 238, 144, 0.4) !important;
            background: var(--hover-overlay-strong) !important;
            border: 1px solid rgba(255, 255, 255, 0.4) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px var(--shadow-medium) !important;
        }
        
        /* Modern User Icon Enhancement */
        .navbar-nav .dropdown-toggle .fas.fa-user-circle {
            font-size: 1.2em !important;
            margin-right: 8px !important;
            filter: drop-shadow(0 0 4px rgba(255, 255, 255, 0.3)) !important;
        }
        
        /* WORKING DROPDOWN - Bootstrap 5.1.3 Compatible */
        .navbar-nav .dropdown-menu {
            background: var(--glass-white) !important;
            backdrop-filter: blur(20px) !important;
            border: 1px solid var(--glass-border-dark) !important;
            border-radius: 16px !important;
            box-shadow: 0 8px 32px var(--shadow-medium) !important;
            padding: 12px 0 !important;
            min-width: 220px !important;
            margin-top: 8px !important;
            position: absolute !important;
            top: 100% !important;
            right: 0 !important;
            left: auto !important;
            z-index: 1060 !important;
            transform: none !important;
        }
        
        @keyframes dropdownSlide {
            from {
                opacity: 0;
                transform: translateY(-10px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        /* Enhanced Dropdown Items */
        .navbar-nav .dropdown-menu .dropdown-item {
            color: var(--text-dark) !important;
            font-weight: 500 !important;
            padding: 12px 20px !important;
            border-radius: 8px !important;
            margin: 2px 8px !important;
            transition: all 0.2s ease !important;
            position: relative !important;
            overflow: hidden !important;
        }
        
        .navbar-nav .dropdown-menu .dropdown-item:hover {
            background: var(--dropdown-hover) !important;
            color: var(--text-blue) !important;
            transform: translateX(4px) !important;
        }
        
        .navbar-nav .dropdown-menu .dropdown-item i {
            width: 20px !important;
            margin-right: 12px !important;
            color: rgba(74, 144, 226, 0.8) !important;
        }
            color: #6b7280 !important;
            transition: color 0.2s ease !important;
        }
        
        .dropdown-menu .dropdown-item:hover i {
            color: #1e40af !important;
        }
        
        /* Dropdown Dividers */
        .dropdown-menu .dropdown-divider {
            margin: 8px 16px !important;
            border-color: rgba(0, 0, 0, 0.1) !important;
            opacity: 0.3 !important;
        }
        
        /* Special styling for admin/organizer items */
        .dropdown-menu .dropdown-item:has(i.fa-shield-alt),
        .dropdown-menu .dropdown-item:has(i.fa-plus),
        .dropdown-menu .dropdown-item:has(i.fa-calendar-plus) {
            background: linear-gradient(135deg, rgba(168, 85, 247, 0.05), rgba(236, 72, 153, 0.05)) !important;
        }
        
        /* Logout item special styling */
        .dropdown-menu .dropdown-item:has(i.fa-sign-out-alt) {
            border-top: 1px solid rgba(0, 0, 0, 0.1) !important;
            margin-top: 8px !important;
        }
        
        .dropdown-menu .dropdown-item:has(i.fa-sign-out-alt):hover {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 127, 0.1)) !important;
            color: #dc2626 !important;
        }
        
        /* ==================== GLOBAL CARD STYLING ==================== */
        /* Softer card appearance */
        .card {
            border: 1px solid var(--card-border) !important;
            border-radius: var(--card-border-radius) !important;
            box-shadow: 0 2px 8px var(--shadow-light) !important;
            transition: all 0.2s ease !important;
        }
        
        .card:hover {
            box-shadow: 0 4px 16px var(--shadow-medium) !important;
            transform: translateY(-1px) !important;
        }
        
        .card-body {
            border-radius: calc(var(--card-border-radius) - 1px) !important;
        }
        
        /* Softer welcome card with green theme */
        .card.bg-primary {
            background: linear-gradient(135deg, var(--brand-success) 0%, var(--brand-primary-medium) 100%) !important;
            border: none !important;
            box-shadow: 0 4px 20px rgba(40, 167, 69, 0.3) !important;
        }
        
        /* ==================== NAVBAR STYLES ==================== */
        /* Clean Professional Navbar */
        .navbar.bg-primary {
            background: linear-gradient(135deg, var(--bg-light-primary) 0%, var(--bg-light-secondary) 30%, var(--bg-light-tertiary) 100%) !important;
            z-index: 1050 !important;
            width: 100% !important;
            max-width: 100% !important;
            padding: 1rem 0 !important; /* Increased navbar height */
            min-height: 70px !important;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1) !important;
        }
        
        /* Fix scrollbar movement and navbar container */
        html, body {
            overflow-x: hidden !important;
            scroll-behavior: smooth !important;
        }
        
        /* Fix navbar container overflow */
        .navbar .container {
            overflow: visible !important;
            max-width: 100% !important;
        }
        
        /* Fix navbar collapse overflow */
        .navbar-collapse {
            overflow: visible !important;
        }
        
        /* Glass morphism overlay for depth */
        .navbar.bg-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            z-index: -1;
        }
        
        /* Subtle light rays effect */
        .navbar.bg-primary::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(
                circle at 30% 20%,
                rgba(255, 255, 255, 0.1) 0%,
                transparent 50%
            );
            animation: lightRays 20s linear infinite;
            pointer-events: none;
            z-index: -1;
        }
        
        @keyframes lightRays {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Enhanced navbar container */
        .navbar .container {
            position: relative;
            z-index: 2;
        }
        /* Primary cards should be more visible */
        .card.bg-primary {
            background: rgba(13, 110, 253, 0.95) !important;
        }
        /* Enhanced ConnectHub branding with drop shadow */
        .navbar-brand {
            color: var(--text-dark) !important;
            text-shadow: 
                1px 1px 2px rgba(255, 255, 255, 0.8), 
                0 0 4px rgba(255, 255, 255, 0.6) !important;
            font-weight: 700 !important;
            letter-spacing: 0.8px !important;
            transition: all 0.3s ease !important;
            filter: drop-shadow(1px 1px 3px rgba(0, 0, 0, 0.1)) !important;
        }
        .navbar-brand:hover {
            color: var(--text-blue) !important;
            text-shadow: 
                2px 2px 4px rgba(255, 255, 255, 0.9), 
                0 0 8px rgba(255, 255, 255, 0.7) !important;
            transform: translateY(-1px) !important;
            filter: drop-shadow(2px 2px 6px rgba(0, 0, 0, 0.15)) !important;
        }
        .navbar-brand i {
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.4) !important;
            filter: drop-shadow(0 0 4px rgba(255, 255, 255, 0.2)) !important;
        }
        
        /* Logo styling */
        .navbar-logo {
            height: 64px !important;
            width: auto !important;
            max-width: none !important;
            object-fit: contain !important;
            filter: drop-shadow(2px 2px 4px rgba(0, 0, 0, 0.3)) drop-shadow(0 0 8px rgba(255, 255, 255, 0.1)) !important;
            transition: all 0.3s ease !important;
        }
        
        .navbar-brand:hover .navbar-logo {
            filter: drop-shadow(3px 3px 6px rgba(0, 0, 0, 0.4)) drop-shadow(0 0 12px rgba(255, 255, 255, 0.2)) !important;
            transform: scale(1.05) !important;
        }
        
        /* Fallback icon styling if logo doesn't load */
        .navbar-brand .fallback-icon {
            display: none;
        }
        
        .navbar-logo:not([src*="logo.png"]), .navbar-logo[src=""] {
            display: none;
        }
        
        .navbar-logo:not([src*="logo.png"]) + .fallback-icon, 
        .navbar-logo[src=""] + .fallback-icon {
            display: inline-block;
        }
        
        /* Dark Mode Styles */
        body.dark-mode {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 30%, #1e1e1e 100%) !important;
            color: #e0e0e0 !important;
        }
        
        /* Dark mode navbar */
        body.dark-mode .navbar.bg-primary {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 30%, #1e1e1e 100%) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        
        /* Dark mode navbar text */
        body.dark-mode .navbar-brand {
            color: #e0e0e0 !important;
            text-shadow: 
                1px 1px 2px rgba(0, 0, 0, 0.8), 
                0 0 4px rgba(255, 255, 255, 0.1) !important;
        }
        
        body.dark-mode .navbar-brand:hover {
            color: #87CEEB !important;
            text-shadow: 
                2px 2px 4px rgba(0, 0, 0, 0.9), 
                0 0 8px rgba(135, 206, 235, 0.3) !important;
        }
        
        body.dark-mode .navbar-nav .nav-link {
            color: #e0e0e0 !important;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.8) !important;
        }
        
        body.dark-mode .navbar-nav .nav-link:hover {
            color: #87CEEB !important;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.9) !important;
            background: rgba(255, 255, 255, 0.1) !important;
        }
        
        body.dark-mode .navbar-nav .dropdown-toggle {
            color: #e0e0e0 !important;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.8) !important;
            background: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
        }
        
        body.dark-mode #darkModeToggle {
            color: #e0e0e0 !important;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.8) !important;
        }
        
        body.dark-mode::before {
            background-image: 
                /* Subtle grid pattern for definition */
                linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px),
                /* Diagonal lines for texture */
                repeating-linear-gradient(45deg, transparent, transparent 35px, rgba(120,119,198,0.05) 35px, rgba(120,119,198,0.05) 70px),
                /* Dark mode radial gradients */
                radial-gradient(circle at 20% 50%, rgba(120, 119, 198, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 99, 132, 0.06) 0%, transparent 50%),
                radial-gradient(circle at 40% 80%, rgba(54, 162, 235, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 60% 30%, rgba(40, 167, 69, 0.06) 0%, transparent 60%),
                radial-gradient(circle at 10% 80%, rgba(32, 201, 151, 0.05) 0%, transparent 55%);
        }
        
        body.dark-mode .card {
            background: rgba(40, 40, 40, 0.95) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #e0e0e0 !important;
        }
        
        body.dark-mode .card.bg-primary {
            background: rgba(13, 110, 253, 0.9) !important;
            color: white !important;
        }
        
        body.dark-mode .text-muted {
            color: #b0b0b0 !important;
        }
        
        body.dark-mode .bg-light {
            background: #2d2d2d !important;
        }
        
        body.dark-mode .border {
            border-color: #404040 !important;
        }
        
        body.dark-mode .alert {
            background: rgba(40, 40, 40, 0.95) !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
            color: #e0e0e0 !important;
        }
        
        body.dark-mode .alert.alert-warning {
            background: rgba(255, 193, 7, 0.2) !important;
            border-color: rgba(255, 193, 7, 0.3) !important;
            color: #ffc107 !important;
        }
        
        /* Dark mode dropdown menu */
        body.dark-mode .dropdown-menu {
            background: rgba(40, 40, 40, 0.95) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #e0e0e0 !important;
        }
        
        body.dark-mode .dropdown-menu .dropdown-item {
            color: #e0e0e0 !important;
        }
        
        body.dark-mode .dropdown-menu .dropdown-item:hover {
            background: rgba(255, 255, 255, 0.1) !important;
            color: #87CEEB !important;
        }
        
        /* Dark mode toggle button */
        #darkModeToggle {
            transition: all 0.3s ease;
        }
        
        #darkModeToggle:hover {
            transform: scale(1.1);
        }
    </style>
    
    <!-- Dark Mode JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const darkModeToggle = document.getElementById('darkModeToggle');
            const darkModeIcon = document.getElementById('darkModeIcon');
            const body = document.body;
            
            // Check for saved dark mode preference
            const isDarkMode = localStorage.getItem('darkMode') === 'true';
            
            // Apply saved preference
            if (isDarkMode) {
                body.classList.add('dark-mode');
                darkModeIcon.className = 'fas fa-sun';
            }
            
            // Toggle dark mode
            darkModeToggle.addEventListener('click', function() {
                body.classList.toggle('dark-mode');
                const isNowDark = body.classList.contains('dark-mode');
                
                // Update icon
                darkModeIcon.className = isNowDark ? 'fas fa-sun' : 'fas fa-moon';
                
                // Save preference
                localStorage.setItem('darkMode', isNowDark);
            });
            
            <?php if (isLoggedIn()): ?>
            // Session timeout warning
            const sessionTimeRemaining = <?php echo getSessionTimeRemaining(); ?>;
            
            if (sessionTimeRemaining > 0 && sessionTimeRemaining <= 300) { // 5 minutes warning
                const minutes = Math.floor(sessionTimeRemaining / 60);
                const seconds = sessionTimeRemaining % 60;
                
                setTimeout(function() {
                    if (confirm(`Your session will expire in ${minutes}:${seconds.toString().padStart(2, '0')}. Would you like to extend your session?`)) {
                        // Make a request to extend session
                        fetch('<?php echo BASE_URL; ?>/extend-session.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            }
                        }).then(() => {
                            location.reload();
                        });
                    }
                }, 1000);
            }
            
            // Auto-logout when session expires
            setTimeout(function() {
                alert('Your session has expired for security reasons. You will be redirected to the login page.');
                window.location.href = '<?php echo BASE_URL; ?>/logout.php';
            }, sessionTimeRemaining * 1000);
            <?php endif; ?>
        });
    </script>
    
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link href="<?php echo $css; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="fixed-top navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
                <img src="assets/images/logo.png" alt="ConnectHub Logo" class="navbar-logo me-2" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                <i class="fas fa-users me-2 fallback-icon" style="display: none;"></i><?php echo APP_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/events.php">Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/groups.php">Groups</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/dashboard.php">Dashboard</a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Membership Required Banner for Unpaid Members -->
                <?php if (isLoggedIn()): ?>
                    <?php 
                    $currentUser = getCurrentUser();
                    
                    try {
                        $userModel = new User();
                        $hasMembership = $userModel->hasMembership($currentUser['id']);
                        $isNewUser = (new DateTime($currentUser['created_at']))->diff(new DateTime())->days === 0;
                    } catch (Exception $e) {
                        // If there's an error, assume no membership to be safe
                        $hasMembership = false;
                        $isNewUser = true;
                    }
                    ?>
                    <?php if (!$hasMembership): ?>
                    <div class="d-flex align-items-center me-3">
                        <div class="alert alert-warning alert-permanent py-1 px-2 mb-0 d-flex align-items-center" style="border-radius: 20px;">
                            <i class="fas fa-crown me-2 text-warning-emphasis"></i>
                            <small class="me-2">
                                <strong>Welcome! Unlock premium features</strong>
                            </small>
                            <a href="<?php echo BASE_URL; ?>/membership.php" class="btn btn-dark btn-xs fw-bold" style="font-size: 0.7rem; padding: 0.2rem 0.5rem;">
                                <i class="fas fa-star me-1"></i>$100/year
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <ul class="navbar-nav">
                    <!-- Dark Mode Toggle -->
                    <li class="nav-item">
                        <button class="btn btn-link nav-link border-0 text-white" id="darkModeToggle" style="background: none;">
                            <i class="fas fa-moon" id="darkModeIcon"></i>
                        </button>
                    </li>
                    
                    <?php if (isLoggedIn()): ?>
                        <?php $currentUser = getCurrentUser(); ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i>
                                <?php echo htmlspecialchars(explode(' ', $currentUser['name'])[0]); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/under-construction.php">
                                    <i class="fas fa-user me-2"></i>Profile
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/under-construction.php">
                                    <i class="fas fa-cog me-2"></i>Settings
                                </a></li>
                                <?php if (isOrganizer()): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/create-group.php">
                                        <i class="fas fa-plus me-2"></i>Create Group
                                    </a></li>
                                <?php endif; ?>
                                <?php if (isAdmin()): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/under-construction.php">
                                        <i class="fas fa-shield-alt me-2"></i>Admin Panel
                                    </a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Flash Messages -->
    <?php $flashMessages = getFlashMessages(); ?>
    <?php if (!empty($flashMessages)): ?>
        <div class="flash-messages-container">
            <?php foreach ($flashMessages as $message): ?>
                <div class="alert alert-<?php echo $message['type'] === 'error' ? 'danger' : $message['type']; ?> alert-dismissible fade show flash-message" role="alert">
                    <?php echo htmlspecialchars($message['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main class="py-4" style="padding-top: 80px !important;">