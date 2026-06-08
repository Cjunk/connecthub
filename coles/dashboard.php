<?php
/**
 * Main Menu/Dashboard for Coles Preferences System
 */

require_once 'includes/auth.php';

// Require login
Auth::requireLogin();
$user = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coles Dashboard - Main Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --coles-red: #E30613;
            --coles-dark-red: #C20510;
            --coles-light-red: #F5E6E7;
            --coles-orange: #FF6B35;
            --coles-green: #008751;
            --coles-dark-green: #006B3F;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        .coles-header {
            background: linear-gradient(135deg, var(--coles-red) 0%, var(--coles-dark-red) 100%);
            box-shadow: 0 4px 12px rgba(227, 6, 19, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
            overflow: hidden;
            transition: all 0.3s ease;
            padding: 1rem 0;
        }
        
        .coles-header.scrolled {
            box-shadow: 0 8px 25px rgba(227, 6, 19, 0.4);
            backdrop-filter: blur(10px);
            padding: 0.5rem 0;
        }
        
        .coles-header.scrolled .coles-brand {
            font-size: 1.4rem;
        }
        
        .coles-header.scrolled .coles-subtitle {
            font-size: 0.8rem;
        }
        
        .coles-header.scrolled .eastern-creek-badge {
            font-size: 0.7rem;
            padding: 0.2rem 0.6rem;
        }
        
        .coles-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255,255,255,0.05) 0%, transparent 50%);
            pointer-events: none;
        }
        
        .coles-brand {
            font-weight: 700;
            font-size: 1.8rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            letter-spacing: -0.5px;
            transition: font-size 0.3s ease;
            color:white;
        }
        
        .coles-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            font-weight: 300;
            transition: font-size 0.3s ease;
            color:white;
        }
        
        .eastern-creek-badge {
            background: rgba(255,255,255,0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.3);
            font-size: 0.8rem;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .welcome-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .menu-card {
            background: white;
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 100%;
            overflow: hidden;
        }
        
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }
        
        .menu-card-header {
            background: linear-gradient(135deg, var(--coles-red) 0%, var(--coles-dark-red) 100%);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        
        .menu-card-header.admin {
            background: linear-gradient(135deg, var(--coles-green) 0%, var(--coles-dark-green) 100%);
        }
        
        .menu-card-header.manager {
            background: linear-gradient(135deg, var(--coles-orange) 0%, #E55A2B 100%);
        }
        
        .menu-card-header.employee {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
        }
        
        .menu-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .menu-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0;
        }
        
        .menu-description {
            font-size: 0.9rem;
            opacity: 0.9;
            margin: 0;
        }
        
        .menu-card-body {
            padding: 1.5rem;
        }
        
        .btn-menu {
            width: 100%;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-primary-menu {
            background: linear-gradient(135deg, var(--coles-red) 0%, var(--coles-dark-red) 100%);
            border: none;
            color: white;
        }
        
        .btn-primary-menu:hover {
            background: linear-gradient(135deg, var(--coles-dark-red) 0%, #A5040D 100%);
            transform: translateY(-1px);
            color: white;
        }
        
        .btn-success-menu {
            background: linear-gradient(135deg, var(--coles-green) 0%, var(--coles-dark-green) 100%);
            border: none;
            color: white;
        }
        
        .btn-success-menu:hover {
            background: linear-gradient(135deg, var(--coles-dark-green) 0%, #005530 100%);
            transform: translateY(-1px);
            color: white;
        }
        
        .btn-warning-menu {
            background: linear-gradient(135deg, var(--coles-orange) 0%, #E55A2B 100%);
            border: none;
            color: white;
        }
        
        .btn-warning-menu:hover {
            background: linear-gradient(135deg, #E55A2B 0%, #D14A1F 100%);
            transform: translateY(-1px);
            color: white;
        }
        
        .role-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
        }
        
        .feature-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .feature-list li:last-child {
            border-bottom: none;
        }
        
        .feature-list i {
            color: var(--coles-green);
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark coles-header">
        <div class="container">
            <div class="d-flex align-items-center">
                <div class="me-4">
                    <div class="coles-brand">
                        <i class="fas fa-store me-2"></i>
                        COLES
                    </div>
                    <div class="coles-subtitle">Management Dashboard</div>
                </div>
                <span class="eastern-creek-badge">
                    <i class="fas fa-map-marker-alt me-1"></i>
                    Eastern Creek Headquarters
                </span>
            </div>
            <div class="navbar-nav ms-auto d-flex align-items-center">
                <div class="me-4 text-end">
                    <div class="text-white">
                        <i class="fas fa-user me-1"></i>
                        <strong><?= htmlspecialchars($user['full_name']) ?></strong>
                    </div>
                    <small class="text-white-50">
                        <span class="badge bg-light text-dark"><?= ucwords(str_replace('_', ' ', $user['role'])) ?></span>
                    </small>
                </div>
                <a class="nav-link text-white" href="logout.php" title="Logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4 pb-5">
        <!-- Welcome Section -->
        <div class="welcome-section p-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-3">
                        <i class="fas fa-tachometer-alt text-primary me-2"></i>
                        Welcome, <?= htmlspecialchars($user['full_name']) ?>!
                    </h1>
                    <p class="lead mb-2">
                        You are logged in as: 
                        <span class="role-badge bg-<?= $user['role'] === 'admin' ? 'success' : ($user['role'] === 'shift_manager' ? 'warning' : 'info') ?> text-white">
                            <i class="fas fa-<?= $user['role'] === 'admin' ? 'crown' : ($user['role'] === 'shift_manager' ? 'users-cog' : 'user') ?> me-1"></i>
                            <?= ucwords(str_replace('_', ' ', $user['role'])) ?>
                        </span>
                    </p>
                    <p class="text-muted">Select from the available options below to manage your tasks and responsibilities.</p>
                </div>
                <div class="col-md-4 text-center">
                    <i class="fas fa-building fa-4x text-primary opacity-25"></i>
                </div>
            </div>
        </div>

        <!-- Menu Cards -->
        <div class="row g-4">
            
            <!-- Holiday Preferences Management -->
            <div class="col-md-6 col-lg-4">
                <div class="card menu-card">
                    <div class="menu-card-header <?= $user['role'] === 'admin' ? 'admin' : ($user['role'] === 'shift_manager' ? 'manager' : 'employee') ?>">
                        <div class="menu-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h3 class="menu-title">Holiday Preferences</h3>
                        <p class="menu-description">Manage employee holiday preferences</p>
                    </div>
                    <div class="menu-card-body">
                        <ul class="feature-list">
                            <?php if ($user['role'] === 'admin'): ?>
                                <li><i class="fas fa-check-circle"></i> View all employees</li>
                                <li><i class="fas fa-check-circle"></i> Edit any preferences</li>
                                <li><i class="fas fa-check-circle"></i> Generate reports</li>
                                <li><i class="fas fa-check-circle"></i> Manage approvals</li>
                            <?php elseif ($user['role'] === 'shift_manager'): ?>
                                <li><i class="fas fa-check-circle"></i> View shift employees</li>
                                <li><i class="fas fa-check-circle"></i> Edit shift preferences</li>
                                <li><i class="fas fa-check-circle"></i> Approve requests</li>
                            <?php else: ?>
                                <li><i class="fas fa-check-circle"></i> View employee data</li>
                                <li><i class="fas fa-check-circle"></i> Read-only access</li>
                            <?php endif; ?>
                        </ul>
                        <div class="mt-3">
                            <?php if ($user['role'] === 'shift_manager' && $user['shift_id']): ?>
                                <a href="shift.php?id=<?= $user['shift_id'] ?>" class="btn btn-warning-menu btn-menu">
                                    <i class="fas fa-users me-2"></i>
                                    Manage My Shift
                                </a>
                            <?php else: ?>
                                <a href="index.php" class="btn btn-primary-menu btn-menu">
                                    <i class="fas fa-calendar-alt me-2"></i>
                                    Open Preferences
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Management (Admin Only) -->
            <?php if ($user['role'] === 'admin'): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card menu-card">
                    <div class="menu-card-header admin">
                        <div class="menu-icon">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <h3 class="menu-title">User Management</h3>
                        <p class="menu-description">Manage system users and permissions</p>
                    </div>
                    <div class="menu-card-body">
                        <ul class="feature-list">
                            <li><i class="fas fa-check-circle"></i> Create new users</li>
                            <li><i class="fas fa-check-circle"></i> Assign roles</li>
                            <li><i class="fas fa-check-circle"></i> Manage permissions</li>
                            <li><i class="fas fa-check-circle"></i> Reset passwords</li>
                        </ul>
                        <div class="mt-3">
                            <button class="btn btn-success-menu btn-menu" onclick="alert('User Management - Coming Soon!')">
                                <i class="fas fa-user-plus me-2"></i>
                                Manage Users
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Reports & Analytics -->
            <?php if ($user['role'] === 'admin' || $user['role'] === 'shift_manager'): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card menu-card">
                    <div class="menu-card-header <?= $user['role'] === 'admin' ? 'admin' : 'manager' ?>">
                        <div class="menu-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h3 class="menu-title">Reports & Analytics</h3>
                        <p class="menu-description">Generate reports and view analytics</p>
                    </div>
                    <div class="menu-card-body">
                        <ul class="feature-list">
                            <li><i class="fas fa-check-circle"></i> Preference statistics</li>
                            <li><i class="fas fa-check-circle"></i> Employee reports</li>
                            <li><i class="fas fa-check-circle"></i> Holiday coverage</li>
                            <?php if ($user['role'] === 'admin'): ?>
                                <li><i class="fas fa-check-circle"></i> System analytics</li>
                            <?php endif; ?>
                        </ul>
                        <div class="mt-3">
                            <button class="btn btn-<?= $user['role'] === 'admin' ? 'success' : 'warning' ?>-menu btn-menu" onclick="alert('Reports & Analytics - Coming Soon!')">
                                <i class="fas fa-chart-line me-2"></i>
                                View Reports
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- System Settings (Admin Only) -->
            <?php if ($user['role'] === 'admin'): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card menu-card">
                    <div class="menu-card-header admin">
                        <div class="menu-icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <h3 class="menu-title">System Settings</h3>
                        <p class="menu-description">Configure system settings and holidays</p>
                    </div>
                    <div class="menu-card-body">
                        <ul class="feature-list">
                            <li><i class="fas fa-check-circle"></i> Manage holidays</li>
                            <li><i class="fas fa-check-circle"></i> System configuration</li>
                            <li><i class="fas fa-check-circle"></i> Backup & restore</li>
                            <li><i class="fas fa-check-circle"></i> Audit logs</li>
                        </ul>
                        <div class="mt-3">
                            <button class="btn btn-success-menu btn-menu" onclick="alert('System Settings - Coming Soon!')">
                                <i class="fas fa-wrench me-2"></i>
                                System Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Profile & Account -->
            <div class="col-md-6 col-lg-4">
                <div class="card menu-card">
                    <div class="menu-card-header employee">
                        <div class="menu-icon">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <h3 class="menu-title">My Account</h3>
                        <p class="menu-description">Manage your profile and settings</p>
                    </div>
                    <div class="menu-card-body">
                        <ul class="feature-list">
                            <li><i class="fas fa-check-circle"></i> Update profile</li>
                            <li><i class="fas fa-check-circle"></i> Change password</li>
                            <li><i class="fas fa-check-circle"></i> Notification settings</li>
                            <li><i class="fas fa-check-circle"></i> Activity history</li>
                        </ul>
                        <div class="mt-3">
                            <button class="btn btn-primary-menu btn-menu" onclick="alert('Profile Management - Coming Soon!')">
                                <i class="fas fa-user-edit me-2"></i>
                                Edit Profile
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Help & Support -->
            <div class="col-md-6 col-lg-4">
                <div class="card menu-card">
                    <div class="menu-card-header employee">
                        <div class="menu-icon">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <h3 class="menu-title">Help & Support</h3>
                        <p class="menu-description">Get help and support resources</p>
                    </div>
                    <div class="menu-card-body">
                        <ul class="feature-list">
                            <li><i class="fas fa-check-circle"></i> User guide</li>
                            <li><i class="fas fa-check-circle"></i> Video tutorials</li>
                            <li><i class="fas fa-check-circle"></i> FAQ</li>
                            <li><i class="fas fa-check-circle"></i> Contact support</li>
                        </ul>
                        <div class="mt-3">
                            <button class="btn btn-primary-menu btn-menu" onclick="alert('Help & Support - Coming Soon!')">
                                <i class="fas fa-life-ring me-2"></i>
                                Get Help
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Quick Actions Footer -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-3">
                        <h6 class="mb-3">Quick Actions</h6>
                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                            <a href="index.php" class="btn btn-outline-primary">
                                <i class="fas fa-table me-1"></i> Full Employee List
                            </a>
                            <?php if ($user['role'] === 'shift_manager' && $user['shift_id']): ?>
                                <a href="shift.php?id=<?= $user['shift_id'] ?>" class="btn btn-outline-warning">
                                    <i class="fas fa-users me-1"></i> My Shift
                                </a>
                            <?php endif; ?>
                            <button class="btn btn-outline-success" onclick="alert('Export Data - Coming Soon!')">
                                <i class="fas fa-download me-1"></i> Export Data
                            </button>
                            <button class="btn btn-outline-info" onclick="alert('System Status - Coming Soon!')">
                                <i class="fas fa-heartbeat me-1"></i> System Status
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fancy header scroll effect
        let lastScrollY = window.scrollY;
        const header = document.querySelector('.coles-header');
        
        function updateHeader() {
            const currentScrollY = window.scrollY;
            
            if (currentScrollY > 50) {
                // Scrolled down - compact header
                header.classList.add('scrolled');
            } else {
                // At top - normal header
                header.classList.remove('scrolled');
            }
            
            lastScrollY = currentScrollY;
        }
        
        // Throttled scroll listener for better performance
        let ticking = false;
        window.addEventListener('scroll', () => {
            if (!ticking) {
                requestAnimationFrame(() => {
                    updateHeader();
                    ticking = false;
                });
                ticking = true;
            }
        });
        
        // Initial check
        updateHeader();
    </script>
</body>
</html>
