<?php
/**
 * Security Dashboard
 * Monitor login attempts and security stats (Admin only)
 */

require '../config/bootstrap.php';

// Require admin access
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    setFlashMessage('error', 'Access denied. Admin privileges required.');
    redirect(BASE_URL . '/dashboard.php');
}

// Get security stats
$stats = Security::getSecurityStats();
$recentFailures = Security::getRecentFailedAttempts(20);

include '../src/views/layout/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-shield-alt me-2"></i>Security Dashboard</h2>
                <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>

            <!-- Security Stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?= number_format($stats['total_attempts']) ?></h4>
                                    <p class="mb-0">Total Attempts (24h)</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-sign-in-alt fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?= number_format($stats['successful_attempts']) ?></h4>
                                    <p class="mb-0">Successful Logins</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?= number_format($stats['failed_attempts']) ?></h4>
                                    <p class="mb-0">Failed Attempts</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-times-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?= $stats['success_rate'] ?>%</h4>
                                    <p class="mb-0">Success Rate</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-chart-line fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Failed Attempts -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>Recent Failed Login Attempts
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recentFailures)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-shield-alt fa-3x text-success mb-3"></i>
                            <h5>No Recent Failed Attempts</h5>
                            <p class="text-muted">Your application is secure!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>IP Address</th>
                                        <th>Email Attempted</th>
                                        <th>Time</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentFailures as $attempt): ?>
                                    <tr>
                                        <td>
                                            <code><?= htmlspecialchars($attempt['ip_address']) ?></code>
                                        </td>
                                        <td>
                                            <?php if ($attempt['email']): ?>
                                                <?= htmlspecialchars($attempt['email']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">Invalid email</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?= date('M j, Y g:i A', strtotime($attempt['attempted_at'])) ?></small>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-warning" 
                                                    onclick="analyzeIP('<?= htmlspecialchars($attempt['ip_address']) ?>')"
                                                    title="Analyze IP">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Security Information -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Rate Limiting Configuration</h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li><strong>Max Attempts:</strong> 5 failed logins</li>
                                <li><strong>Time Window:</strong> 15 minutes</li>
                                <li><strong>Auto Reset:</strong> On successful login</li>
                                <li><strong>IP Detection:</strong> Proxy-aware</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Session Security</h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li><strong>Strict Mode:</strong> ✅ Enabled</li>
                                <li><strong>HTTP Only:</strong> ✅ Enabled</li>
                                <li><strong>Secure Cookies:</strong> ✅ HTTPS Only</li>
                                <li><strong>SameSite:</strong> ✅ Lax</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function analyzeIP(ip) {
    // Simple IP analysis - in production, you might integrate with threat intelligence APIs
    const message = `IP Analysis for: ${ip}\n\n` +
                   `This would typically show:\n` +
                   `- Geolocation data\n` +
                   `- Threat intelligence reports\n` +
                   `- Historical abuse patterns\n` +
                   `- ISP information\n\n` +
                   `Consider integrating with services like:\n` +
                   `- AbuseIPDB\n` +
                   `- VirusTotal\n` +
                   `- MaxMind GeoIP`;
    
    alert(message);
}
</script>

<?php include '../src/views/layout/footer.php'; ?>