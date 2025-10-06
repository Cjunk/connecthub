<?php
/**
 * Create Stripe Payment Intent
 */

require_once '../../config/constants.php';
require_once '../../config/bootstrap.php';

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
    
    // Check if user needs membership payment
    $userModel = new User();
    if ($userModel->hasMembership($currentUser['id'])) {
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
        'metadata[user_id]' => $currentUser['id'],
        'metadata[user_email]' => $currentUser['email'],
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
    $db = Database::getInstance();
    $sql = "INSERT INTO payments (user_id, amount, currency, type, status, stripe_payment_intent_id, description, created_at) 
            VALUES (:user_id, :amount, :currency, :type, :status, :stripe_id, :description, CURRENT_TIMESTAMP)";
    
    $db->query($sql, [
        ':user_id' => $currentUser['id'],
        ':amount' => $amount / 100, // Convert cents to dollars
        ':currency' => strtoupper($currency),
        ':type' => $payment_type,
        ':status' => 'pending',
        ':stripe_id' => $payment_intent['id'],
        ':description' => 'Annual membership payment'
    ]);
    
    // Return client secret
    echo json_encode([
        'clientSecret' => $payment_intent['client_secret']
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>