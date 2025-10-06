<?php
/**
 * Add Payments Table to PostgreSQL Database
 */

// Load configuration
require_once '../config/constants.php';
require_once '../config/database.php';

echo "<h1>Adding Payments Table to ConnectHub Database</h1>\n";

try {
    $db = Database::getInstance();
    
    // Create payments table
    $sql = "
    CREATE TABLE IF NOT EXISTS payments (
        id SERIAL PRIMARY KEY,
        user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        amount DECIMAL(10, 2) NOT NULL,
        currency VARCHAR(3) DEFAULT 'USD',
        type VARCHAR(20) NOT NULL CHECK (type IN ('membership', 'event', 'other')),
        status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'completed', 'failed', 'refunded')),
        payment_method VARCHAR(50),
        transaction_id VARCHAR(255),
        stripe_payment_intent_id VARCHAR(255),
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    
    -- Create index for better performance
    CREATE INDEX IF NOT EXISTS idx_payments_user ON payments(user_id);
    CREATE INDEX IF NOT EXISTS idx_payments_status ON payments(status);
    CREATE INDEX IF NOT EXISTS idx_payments_stripe_id ON payments(stripe_payment_intent_id);
    ";
    
    $db->getConnection()->exec($sql);
    
    echo "<p>✅ Payments table created successfully!</p>\n";
    echo "<h3>Table Structure:</h3>\n";
    echo "<ul>\n";
    echo "<li><strong>id</strong> - Primary key</li>\n";
    echo "<li><strong>user_id</strong> - References users table</li>\n";
    echo "<li><strong>amount</strong> - Payment amount in dollars</li>\n";
    echo "<li><strong>currency</strong> - USD, EUR, etc.</li>\n";
    echo "<li><strong>type</strong> - membership, event, other</li>\n";
    echo "<li><strong>status</strong> - pending, completed, failed, refunded</li>\n";
    echo "<li><strong>stripe_payment_intent_id</strong> - Stripe tracking</li>\n";
    echo "<li><strong>description</strong> - Payment description</li>\n";
    echo "<li><strong>created_at/updated_at</strong> - Timestamps</li>\n";
    echo "</ul>\n";
    
    echo "<p><a href='membership.php' class='btn btn-primary'>Test Membership Payment</a></p>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
    echo "<h3>Troubleshooting:</h3>\n";
    echo "<ul>\n";
    echo "<li>Make sure PostgreSQL is running</li>\n";
    echo "<li>Check database credentials in config/local_config.php</li>\n";
    echo "<li>Ensure the connecthub_admin user has proper permissions</li>\n";
    echo "</ul>\n";
}
?>