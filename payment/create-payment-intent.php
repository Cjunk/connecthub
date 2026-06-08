<?php
/**
 * Create Stripe Payment Intent
 */

require_once '../config/constants.php';
require_once '../config/bootstrap.php';
require_once '../src/models/Payment.php';

// Set content type
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Only POST requests allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $diagnosticRef = 'pi_' . date('Ymd_His') . '_' . substr(uniqid('', true), -6);

    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid request data');
    }
    
    $amount = $input['amount'] ?? 0;
    $currency = $input['currency'] ?? 'usd';
    $payment_type = $input['payment_type'] ?? 'membership';
    
    // Validate amount
    if ($amount < 50) { // Minimum $0.50
        throw new Exception('Invalid payment amount');
    }
    
    // Validate payment type
    if ($payment_type !== 'membership') {
        throw new Exception('Invalid payment type');
    }
    
    $currentUser = getCurrentUser();
    $sessionUserId = $_SESSION['user_id'] ?? null;
    $userId = $currentUser['id'] ?? getCurrentUserId();
    $userEmail = $currentUser['email'] ?? ($_SESSION['user_email'] ?? '');

    $userModel = new User();

    // Recover from stale sessions where user_id no longer exists in users table.
    $resolvedUser = null;
    if (!empty($userId)) {
        $resolvedUser = $userModel->findById((int)$userId);
    }
    if (!$resolvedUser && !empty($userEmail)) {
        $resolvedUser = $userModel->findByEmail($userEmail);
        if ($resolvedUser && !empty($resolvedUser['id'])) {
            $userId = (int)$resolvedUser['id'];
            $_SESSION['user_id'] = $userId;
        }
    }
    if (!$resolvedUser || empty($userId)) {
        throw new Exception('Session user record not found. Please log out and log in again.');
    }

    $userEmail = $resolvedUser['email'] ?? $userEmail;

    // Check if user needs membership payment
    if ($userModel->hasMembership((int)$userId)) {
        throw new Exception('Membership is already active');
    }
    
    // Include Stripe PHP SDK (you'll need to install this via Composer)
    // For now, we'll use curl to create the payment intent
    
    $stripe_secret_key = STRIPE_SECRET_KEY;
    
    if (empty($stripe_secret_key)) {
        throw new Exception('Stripe not configured');
    }
    
    // Create Stripe Payment Intent using curl
    $payment_intent_data = [
        'amount' => $amount,
        'currency' => $currency,
        'automatic_payment_methods[enabled]' => 'true',
        'metadata[user_id]' => $userId,
        'metadata[user_email]' => $userEmail,
        'metadata[payment_type]' => $payment_type
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/payment_intents');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payment_intent_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $stripe_secret_key,
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        $error_data = json_decode($response, true);
        throw new Exception($error_data['error']['message'] ?? 'Stripe API error');
    }
    
    $payment_intent = json_decode($response, true);
    
    // Store payment intent in database for tracking
    $paymentModel = new Payment();
    $paymentModel->create([
        'user_id' => (int)$userId,
        'amount' => $amount / 100, // Convert cents to dollars
        'currency' => strtoupper($currency),
        'type' => $payment_type,
        'status' => 'pending',
        'stripe_payment_intent_id' => $payment_intent['id'],
        'description' => 'Annual membership payment'
    ]);
    
    // Return client secret
    echo json_encode([
        'clientSecret' => $payment_intent['client_secret']
    ]);
    
} catch (Exception $e) {
    $details = [
        'reference' => isset($diagnosticRef) ? $diagnosticRef : ('pi_' . date('Ymd_His')),
        'timestamp_utc' => gmdate('c'),
        'endpoint' => '/payment/create-payment-intent.php',
        'session_user_id' => isset($sessionUserId) ? (int)$sessionUserId : 0,
        'user_id' => isset($userId) ? (int)$userId : 0,
        'amount' => isset($amount) ? (int)$amount : 0,
        'currency' => isset($currency) ? strtoupper((string)$currency) : '',
        'payment_type' => isset($payment_type) ? (string)$payment_type : '',
        'http_status' => 400
    ];

    error_log('Payment intent error [' . $details['reference'] . ']: ' . $e->getMessage() . ' | details=' . json_encode($details));
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage(),
        'details' => $details
    ]);
}
?>
