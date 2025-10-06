<?php
/**
 * Check Recent Payments in Database
 */

require_once '../config/constants.php';
require_once '../config/bootstrap.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Verification - ConnectHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <h1>💳 Payment Verification</h1>
                <p class="text-muted">Let's check if your payment was really processed...</p>
                
                <?php
                try {
                    $db = Database::getInstance();
                    
                    echo "<div class='card mb-4'>";
                    echo "<div class='card-header'><h3>🔍 Database Check</h3></div>";
                    echo "<div class='card-body'>";
                    
                    // Check if payments table exists
                    $tableCheck = $db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'payments'");
                    
                    if (!$tableCheck || count($tableCheck) === 0) {
                        echo "<div class='alert alert-warning'>⚠️ Payments table doesn't exist yet. <a href='add-payments-table.php'>Create it here</a></div>";
                    } else {
                        echo "<div class='alert alert-success'>✅ Payments table exists</div>";
                        
                        // Get all payments for current user
                        $userPayments = $db->query(
                            "SELECT * FROM payments WHERE user_id = :user_id ORDER BY created_at DESC",
                            [':user_id' => $currentUser['id']]
                        );
                        
                        if (!$userPayments || count($userPayments) === 0) {
                            echo "<div class='alert alert-info'>ℹ️ No payments found for your account</div>";
                        } else {
                            echo "<h4>Your Payment History:</h4>";
                            echo "<div class='table-responsive'>";
                            echo "<table class='table table-striped'>";
                            echo "<thead><tr><th>Date</th><th>Amount</th><th>Type</th><th>Status</th><th>Stripe ID</th><th>Description</th></tr></thead>";
                            echo "<tbody>";
                            
                            foreach ($userPayments as $payment) {
                                $statusClass = $payment['status'] === 'completed' ? 'success' : 
                                             ($payment['status'] === 'failed' ? 'danger' : 'warning');
                                echo "<tr>";
                                echo "<td>" . date('M j, Y g:i A', strtotime($payment['created_at'])) . "</td>";
                                echo "<td>$" . number_format($payment['amount'], 2) . " " . strtoupper($payment['currency']) . "</td>";
                                echo "<td>" . ucfirst($payment['type']) . "</td>";
                                echo "<td><span class='badge bg-{$statusClass}'>" . ucfirst($payment['status']) . "</span></td>";
                                echo "<td><small>" . ($payment['stripe_payment_intent_id'] ?: 'N/A') . "</small></td>";
                                echo "<td>" . ($payment['description'] ?: 'N/A') . "</td>";
                                echo "</tr>";
                            }
                            
                            echo "</tbody></table>";
                            echo "</div>";
                        }
                        
                        // Get total payments count
                        $totalPayments = $db->query("SELECT COUNT(*) as count FROM payments");
                        echo "<p class='text-muted mt-3'>Total payments in system: " . $totalPayments[0]['count'] . "</p>";
                    }
                    
                    echo "</div></div>";
                    
                    // Check user membership status
                    echo "<div class='card mb-4'>";
                    echo "<div class='card-header'><h3>👤 Your Membership Status</h3></div>";
                    echo "<div class='card-body'>";
                    
                    $userModel = new User();
                    $hasMembership = $userModel->hasMembership($currentUser['id']);
                    
                    if ($hasMembership) {
                        echo "<div class='alert alert-success'>";
                        echo "✅ <strong>Active Membership</strong><br>";
                        echo "Your membership is currently active!";
                        echo "</div>";
                    } else {
                        echo "<div class='alert alert-warning'>";
                        echo "⚠️ <strong>No Active Membership</strong><br>";
                        echo "You don't have an active membership yet.";
                        echo "</div>";
                    }
                    
                    // Show user details
                    echo "<h5>Account Details:</h5>";
                    echo "<ul>";
                    echo "<li><strong>Name:</strong> " . htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']) . "</li>";
                    echo "<li><strong>Email:</strong> " . htmlspecialchars($currentUser['email']) . "</li>";
                    echo "<li><strong>Role:</strong> " . htmlspecialchars($currentUser['role']) . "</li>";
                    echo "<li><strong>Member Since:</strong> " . date('M j, Y', strtotime($currentUser['created_at'])) . "</li>";
                    echo "</ul>";
                    
                    echo "</div></div>";
                    
                } catch (Exception $e) {
                    echo "<div class='alert alert-danger'>❌ Error: " . $e->getMessage() . "</div>";
                }
                ?>
                
                <div class="card">
                    <div class="card-header"><h3>🌐 External Verification</h3></div>
                    <div class="card-body">
                        <h5>Check Your Stripe Dashboard:</h5>
                        <ol>
                            <li><a href="https://dashboard.stripe.com/test/payments" target="_blank">Open Stripe Test Dashboard</a></li>
                            <li>Look for recent payments in the last few minutes</li>
                            <li>Check if the payment amount ($50.00) and customer email match</li>
                            <li>Status should show "Succeeded"</li>
                        </ol>
                        
                        <h5 class="mt-4">Test Another Payment:</h5>
                        <p>
                            <a href="membership.php" class="btn btn-primary">Make Another Test Payment</a>
                            <a href="dashboard.php" class="btn btn-secondary">Go to Dashboard</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>