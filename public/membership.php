<?php
require_once '../config/constants.php';
require_once '../config/bootstrap.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(BASE_URL . '/login.php');
}

$currentUser = getCurrentUser();
$pageTitle = 'Membership Payment';

// Check if user needs to pay membership
$userModel = new User();
$needsPayment = !$userModel->hasMembership($currentUser['id']);

// If user doesn't need payment, redirect to dashboard
if (!$needsPayment) {
    setFlashMessage('info', 'Your membership is already active!');
    redirect(BASE_URL . '/dashboard.php');
}

// Set membership fee (from constants)
$membershipFee = 100.00; // Annual membership fee
$currency = 'USD';

// Check if user is new (registered in last 24 hours)
$isNewUser = (new DateTime($currentUser['created_at']))->diff(new DateTime())->days === 0;

?>

<?php include '../src/views/layouts/header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Membership Info -->
            <div class="card mb-4">
                <div class="card-body text-center">
                    <i class="fas fa-crown fa-3x text-warning mb-3"></i>
                    <h2 class="card-title">ConnectHub Membership</h2>
                    <p class="card-text lead">Join our community and unlock all features</p>
                    
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="feature-item">
                                <i class="fas fa-users text-primary mb-2"></i>
                                <h6>Join Groups</h6>
                                <p class="text-muted small">Access to all public and private groups</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="feature-item">
                                <i class="fas fa-calendar-check text-success mb-2"></i>
                                <h6>Attend Events</h6>
                                <p class="text-muted small">RSVP and attend unlimited events</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="feature-item">
                                <i class="fas fa-comments text-info mb-2"></i>
                                <h6>Community Features</h6>
                                <p class="text-muted small">Chat, networking, and premium features</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-credit-card me-2"></i>Payment Details
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Membership Type:</h6>
                            <p class="mb-1"><strong>Annual Membership</strong></p>
                            <small class="text-muted">Valid for 12 months from purchase date</small>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h6>Total Amount:</h6>
                            <h3 class="text-primary mb-0">$<?php echo number_format($membershipFee, 2); ?></h3>
                            <small class="text-muted">USD per year</small>
                        </div>
                    </div>

                    <div id="payment-form">
                        <div class="mb-3">
                            <label for="card-element" class="form-label">Credit or Debit Card</label>
                            <div id="card-element" class="form-control" style="height: 40px; padding: 12px;">
                                <!-- Stripe Elements will create form elements here -->
                            </div>
                            <div id="card-errors" role="alert" class="text-danger mt-2"></div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="<?php echo BASE_URL; ?>/under-construction.php" target="_blank">Terms of Service</a> 
                                    and <a href="<?php echo BASE_URL; ?>/under-construction.php" target="_blank">Privacy Policy</a>
                                </label>
                            </div>
                        </div>

                        <button id="submit-payment" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-lock me-2"></i>Pay $<?php echo number_format($membershipFee, 2); ?> Securely
                        </button>

                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                Payments are securely processed by Stripe. We never store your card details.
                            </small>
                        </div>
                    </div>

                    <!-- Loading state -->
                    <div id="payment-loading" style="display: none;" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Processing payment...</span>
                        </div>
                        <p class="mt-2">Processing your payment...</p>
                    </div>
                </div>
            </div>

            <!-- Security Notice -->
            <div class="alert alert-info mt-4">
                <h6><i class="fas fa-info-circle me-2"></i>Payment Security</h6>
                <ul class="mb-0">
                    <li>All payments are processed securely through Stripe</li>
                    <li>Your card details are never stored on our servers</li>
                    <li>You will receive an email confirmation after successful payment</li>
                    <li>Membership auto-renews annually unless cancelled</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Stripe JavaScript -->
<script src="https://js.stripe.com/v3/"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Create Stripe instance
    const stripe = Stripe('<?php echo STRIPE_PUBLIC_KEY; ?>');
    const elements = stripe.elements();

    // Create card element
    const cardElement = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#424770',
                '::placeholder': {
                    color: '#aab7c4',
                },
            },
        },
    });

    cardElement.mount('#card-element');

    // Handle real-time validation errors from the card Element
    cardElement.on('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });

    // Handle form submission
    const form = document.getElementById('payment-form');
    const submitButton = document.getElementById('submit-payment');
    const loadingDiv = document.getElementById('payment-loading');

    submitButton.addEventListener('click', async function(event) {
        event.preventDefault();

        // Check terms checkbox
        const termsCheckbox = document.getElementById('terms');
        if (!termsCheckbox.checked) {
            alert('Please accept the Terms of Service and Privacy Policy');
            return;
        }

        // Disable button and show loading
        submitButton.disabled = true;
        form.style.display = 'none';
        loadingDiv.style.display = 'block';

        // Create payment intent
        try {
            const response = await fetch('<?php echo BASE_URL; ?>/payment/create-payment-intent.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    amount: <?php echo $membershipFee * 100; ?>, // Amount in cents
                    currency: '<?php echo strtolower($currency); ?>',
                    payment_type: 'membership'
                })
            });

            const { clientSecret, error } = await response.json();

            if (error) {
                throw new Error(error);
            }

            // Confirm payment with Stripe
            const { error: stripeError, paymentIntent } = await stripe.confirmCardPayment(clientSecret, {
                payment_method: {
                    card: cardElement,
                    billing_details: {
                        name: '<?php echo htmlspecialchars($currentUser['name']); ?>',
                        email: '<?php echo htmlspecialchars($currentUser['email']); ?>'
                    }
                }
            });

            if (stripeError) {
                throw new Error(stripeError.message);
            }

            // Payment successful
            if (paymentIntent.status === 'succeeded') {
                // Redirect to success page
                window.location.href = '<?php echo BASE_URL; ?>/payment/success.php?payment_intent=' + paymentIntent.id;
            }

        } catch (error) {
            console.error('Payment error:', error);
            
            // Show error and restore form
            alert('Payment failed: ' + error.message);
            submitButton.disabled = false;
            form.style.display = 'block';
            loadingDiv.style.display = 'none';
        }
    });
});
</script>

<?php include '../src/views/layouts/footer.php'; ?>