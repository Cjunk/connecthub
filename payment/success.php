<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/bootstrap.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(BASE_URL . '/login.php');
}

$currentUser = getCurrentUser();
$currentUserId = $currentUser['id'] ?? getCurrentUserId();

if (empty($currentUserId)) {
    redirect(BASE_URL . '/login.php');
}

if (!$currentUser) {
    $userModel = new User();
    $currentUser = $userModel->findById($currentUserId);
}
$pageTitle = 'Payment Successful';

// Get payment intent ID from URL
$payment_intent_id = $_GET['payment_intent'] ?? '';

$quick_preview = false;
$already_processed = true;
$payment = [
    'amount' => 100,
    'currency' => 'usd'
];

if (empty($payment_intent_id)) {
    $quick_preview = true;
    $payment_intent_id = 'preview';
}

if (!$quick_preview) {
try {
    // Verify payment with Stripe and update database
    $db = Database::getInstance();
    
    // Find the payment record
    $sql = "SELECT * FROM payments WHERE stripe_payment_intent_id = :payment_intent_id AND user_id = :user_id";
    $payment = $db->fetch($sql, [
        ':payment_intent_id' => $payment_intent_id,
        ':user_id' => $currentUserId
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
            $driver = $db->getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'pgsql') {
                $colCheck = $db->fetch("SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'users' AND column_name = 'membership_expires' LIMIT 1");
            } else {
                $colCheck = $db->fetch("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'membership_expires' LIMIT 1");
            }
            $membershipColumn = $colCheck ? 'membership_expires' : 'membership_expires_at';

            $sql = "UPDATE users SET {$membershipColumn} = :expires, updated_at = CURRENT_TIMESTAMP WHERE id = :user_id";
            $db->query($sql, [
                ':expires' => $membership_expires,
                ':user_id' => $currentUserId
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
}

?>

<?php include __DIR__ . '/../src/views/layouts/header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card border-success shadow-sm">
                <div class="card-body text-center py-5">
                    <div class="success-animation mb-3">
                        <i class="fas fa-check-circle fa-4x text-success"></i>
                    </div>

                    <h2 class="text-success mb-2">
                        <?php echo $already_processed ? 'Membership Confirmed' : 'Payment Successful'; ?>
                    </h2>
                    <p class="text-muted mb-4">
                        <?php if ($already_processed): ?>
                            Your membership is already active.
                        <?php else: ?>
                            Thanks! Your membership is now active and ready to use.
                        <?php endif; ?>
                    </p>

                    <div class="bg-light rounded p-3 mb-4 text-start">
                        <p class="mb-1"><strong>Amount:</strong> $<?php echo number_format($payment['amount'], 2); ?> <?php echo htmlspecialchars(strtoupper($payment['currency'])); ?></p>
                        <p class="mb-1"><strong>Status:</strong> <span class="badge bg-success">Completed</span></p>
                        <p class="mb-0"><strong>Reference:</strong> <?php echo htmlspecialchars($payment_intent_id); ?></p>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="<?php echo BASE_URL; ?>/dashboard.php" class="btn btn-primary">
                            <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                        </a>
                        <a href="<?php echo BASE_URL; ?>/membership.php" class="btn btn-outline-secondary">
                            <i class="fas fa-receipt me-2"></i>Back to Membership
                        </a>
                    </div>
                </div>
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

<?php include __DIR__ . '/../src/views/layouts/footer.php'; ?>
