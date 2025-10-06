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
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <?php if (file_exists(ROOT_PATH . '/assets/css/clean-style.css')): ?>
        <link href="assets/css/clean-style.css" rel="stylesheet">
    <?php endif; ?>
    
    <!-- Ensure background texture loads -->
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e8f5e8 30%, #e9ecef 100%) !important;
            background-attachment: fixed !important;
            position: relative;
            min-height: 100vh;
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
        .container, main, .navbar {
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
            padding-top: 80px !important;
        }
        body {
            padding-top: 0 !important;
        }
        /* Primary cards should be more visible */
        .card.bg-primary {
            background: rgba(13, 110, 253, 0.95) !important;
        }
        /* Enhanced ConnectHub branding with drop shadow */
        .navbar-brand {
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3), 
                         0 0 8px rgba(255, 255, 255, 0.1) !important;
            font-weight: 600 !important;
            letter-spacing: 0.5px !important;
            transition: all 0.3s ease !important;
        }
        .navbar-brand:hover {
            text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.4), 
                         0 0 12px rgba(255, 255, 255, 0.2) !important;
            transform: translateY(-1px) !important;
        }
        .navbar-brand i {
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.4) !important;
            filter: drop-shadow(0 0 4px rgba(255, 255, 255, 0.2)) !important;
        }
        
        /* Logo styling */
        .navbar-logo {
            height: 32px !important;
            width: auto !important;
            max-width: 40px !important;
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
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
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
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i>
                                <?php echo htmlspecialchars(explode(' ', $currentUser['name'])[0]); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/under-construction.php">
                                    <i class="fas fa-user me-2"></i>Profile
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/under-construction.php">
                                    <i class="fas fa-cog me-2"></i>Settings
                                </a></li>
                                <?php if (isOrganizer()): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/under-construction.php">
                                        <i class="fas fa-plus me-2"></i>Create Group
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/under-construction.php">
                                        <i class="fas fa-calendar-plus me-2"></i>Create Event
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