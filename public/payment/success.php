<?php
require_once '../../config/constants.php';
require_once '../../config/bootstrap.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(BASE_URL . '/login.php');
}

$currentUser = getCurrentUser();
$pageTitle = 'Payment Successful';

// Get payment intent ID from URL
$payment_intent_id = $_GET['payment_intent'] ?? '';

if (empty($payment_intent_id)) {
    setFlashMessage('error', 'Invalid payment confirmation');
    redirect(BASE_URL . '/dashboard.php');
}

try {
    // Verify payment with Stripe and update database
    $db = Database::getInstance();
    
    // Find the payment record
    $sql = "SELECT * FROM payments WHERE stripe_payment_intent_id = :payment_intent_id AND user_id = :user_id";
    $payment = $db->fetch($sql, [
        ':payment_intent_id' => $payment_intent_id,
        ':user_id' => $currentUser['id']
    ]);
    
    if (!$payment) {
        throw new Exception('Payment record not found');
    }
    
    // If payment is already completed, just show success
    if ($payment['status'] === 'completed') {
        $already_processed = true;
    } else {
        // Verify with Stripe API
        $stripe_secret_key = STRIPE_SECRET_KEY;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/payment_intents/' . $payment_intent_id);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $stripe_secret_key
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            throw new Exception('Failed to verify payment with Stripe');
        }
        
        $stripe_payment = json_decode($response, true);
        
        if ($stripe_payment['status'] !== 'succeeded') {
            throw new Exception('Payment was not successful');
        }
        
        // Update payment status and user membership
        $db->beginTransaction();
        
        try {
            // Update payment status
            $sql = "UPDATE payments SET status = 'completed', updated_at = CURRENT_TIMESTAMP WHERE id = :id";
            $db->query($sql, [':id' => $payment['id']]);
            
            // Update user membership (add 1 year from now)
            $membership_expires = date('Y-m-d H:i:s', strtotime('+1 year'));
            $sql = "UPDATE users SET membership_expires = :expires, updated_at = CURRENT_TIMESTAMP WHERE id = :user_id";
            $db->query($sql, [
                ':expires' => $membership_expires,
                ':user_id' => $currentUser['id']
            ]);
            
            $db->commit();
            $already_processed = false;
            
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }
    
} catch (Exception $e) {
    setFlashMessage('error', 'Payment verification failed: ' . $e->getMessage());
    redirect(BASE_URL . '/membership.php');
}

?>

<?php include '../../src/views/layouts/header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Success Message -->
            <div class="card border-success">
                <div class="card-body text-center py-5">
                    <div class="success-animation mb-4">
                        <i class="fas fa-check-circle fa-5x text-success"></i>
                    </div>
                    
                    <h2 class="text-success mb-3">
                        <?php echo $already_processed ? 'Membership Confirmed' : 'Payment Successful!'; ?>
                    </h2>
                    
                    <p class="lead mb-4">
                        <?php if ($already_processed): ?>
                            Your membership has been confirmed and is active.
                        <?php else: ?>
                            Thank you for your payment! Your ConnectHub membership is now active.
                        <?php endif; ?>
                    </p>
                    
                    <!-- Payment Details -->
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Payment Details</h6>
                                    <p class="mb-1"><strong>Amount:</strong> $<?php echo number_format($payment['amount'], 2); ?></p>
                                    <p class="mb-1"><strong>Currency:</strong> <?php echo $payment['currency']; ?></p>
                                    <p class="mb-0"><strong>Payment ID:</strong> <?php echo htmlspecialchars($payment_intent_id); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Membership Details</h6>
                                    <p class="mb-1"><strong>Type:</strong> Annual Membership</p>
                                    <p class="mb-1"><strong>Status:</strong> <span class="badge bg-success">Active</span></p>
                                    <p class="mb-0"><strong>Valid Until:</strong> <?php echo date('M d, Y', strtotime('+1 year')); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- What's Next -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-star me-2"></i>What You Can Do Now</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                        <h6>Join Groups</h6>
                                        <p class="text-muted small">Browse and join groups that interest you</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <i class="fas fa-calendar-check fa-2x text-success mb-2"></i>
                                        <h6>Attend Events</h6>
                                        <p class="text-muted small">RSVP to upcoming events in your area</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <i class="fas fa-network-wired fa-2x text-info mb-2"></i>
                                        <h6>Network</h6>
                                        <p class="text-muted small">Connect with like-minded people</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="<?php echo BASE_URL; ?>/dashboard.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                        </a>
                        <a href="<?php echo BASE_URL; ?>/under-construction.php" class="btn btn-outline-success btn-lg">
                            <i class="fas fa-search me-2"></i>Browse Groups
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Email Confirmation Notice -->
            <div class="alert alert-info mt-4">
                <h6><i class="fas fa-envelope me-2"></i>Email Confirmation</h6>
                <p class="mb-0">
                    A confirmation email has been sent to <strong><?php echo htmlspecialchars($currentUser['email']); ?></strong> 
                    with your payment receipt and membership details.
                </p>
            </div>
        </div>
    </div>
</div>

<style>
.success-animation {
    animation: bounceIn 0.6s ease-out;
}

@keyframes bounceIn {
    0% {
        transform: scale(0.3);
        opacity: 0;
    }
    50% {
        transform: scale(1.05);
    }
    70% {
        transform: scale(0.9);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}
</style>

<?php include '../../src/views/layouts/footer.php'; ?>