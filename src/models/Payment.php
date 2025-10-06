<?php
/**
 * Payment Model
 */

class Payment {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create a new payment record
     */
    public function create($data) {
        $sql = "INSERT INTO payments (user_id, amount, currency, type, status, stripe_payment_intent_id, description) 
                VALUES (:user_id, :amount, :currency, :type, :status, :stripe_id, :description) 
                RETURNING id";
        
        $params = [
            ':user_id' => $data['user_id'],
            ':amount' => $data['amount'],
            ':currency' => $data['currency'],
            ':type' => $data['type'],
            ':status' => $data['status'] ?? 'pending',
            ':stripe_id' => $data['stripe_payment_intent_id'] ?? null,
            ':description' => $data['description'] ?? null
        ];
        
        $result = $this->db->fetch($sql, $params);
        return $result['id'];
    }
    
    /**
     * Find payment by ID
     */
    public function findById($id) {
        $sql = "SELECT * FROM payments WHERE id = :id";
        return $this->db->fetch($sql, [':id' => $id]);
    }
    
    /**
     * Find payment by Stripe payment intent ID
     */
    public function findByStripeId($stripe_id) {
        $sql = "SELECT * FROM payments WHERE stripe_payment_intent_id = :stripe_id";
        return $this->db->fetch($sql, [':stripe_id' => $stripe_id]);
    }
    
    /**
     * Update payment status
     */
    public function updateStatus($id, $status, $transaction_id = null) {
        $sql = "UPDATE payments SET status = :status, transaction_id = :transaction_id, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $params = [
            ':id' => $id,
            ':status' => $status,
            ':transaction_id' => $transaction_id
        ];
        
        return $this->db->query($sql, $params);
    }
    
    /**
     * Get user's payment history
     */
    public function getUserPayments($userId, $limit = 10) {
        $sql = "SELECT * FROM payments WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit";
        return $this->db->fetchAll($sql, [':user_id' => $userId, ':limit' => $limit]);
    }
    
    /**
     * Get user's last successful membership payment
     */
    public function getLastMembershipPayment($userId) {
        $sql = "SELECT * FROM payments 
                WHERE user_id = :user_id 
                AND type = 'membership' 
                AND status = 'completed' 
                ORDER BY created_at DESC 
                LIMIT 1";
        return $this->db->fetch($sql, [':user_id' => $userId]);
    }
    
    /**
     * Process successful payment
     */
    public function processSuccessfulPayment($paymentId, $stripePaymentIntent) {
        $this->db->beginTransaction();
        
        try {
            $payment = $this->findById($paymentId);
            if (!$payment) {
                throw new Exception('Payment not found');
            }
            
            // Update payment status
            $this->updateStatus($paymentId, 'completed', $stripePaymentIntent['id']);
            
            // If it's a membership payment, update user's membership
            if ($payment['type'] === 'membership') {
                $userModel = new User();
                $membershipExpires = date('Y-m-d H:i:s', strtotime('+1 year'));
                
                $sql = "UPDATE users SET membership_expires = :expires, updated_at = CURRENT_TIMESTAMP WHERE id = :user_id";
                $this->db->query($sql, [
                    ':expires' => $membershipExpires,
                    ':user_id' => $payment['user_id']
                ]);
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Get payment statistics for admin
     */
    public function getPaymentStats() {
        $stats = [];
        
        // Total revenue
        $sql = "SELECT SUM(amount) as total_revenue FROM payments WHERE status = 'completed'";
        $result = $this->db->fetch($sql);
        $stats['total_revenue'] = $result['total_revenue'] ?? 0;
        
        // Monthly revenue
        $sql = "SELECT SUM(amount) as monthly_revenue FROM payments 
                WHERE status = 'completed' 
                AND created_at >= DATE_TRUNC('month', CURRENT_DATE)";
        $result = $this->db->fetch($sql);
        $stats['monthly_revenue'] = $result['monthly_revenue'] ?? 0;
        
        // Total payments
        $sql = "SELECT COUNT(*) as total_payments FROM payments WHERE status = 'completed'";
        $result = $this->db->fetch($sql);
        $stats['total_payments'] = $result['total_payments'] ?? 0;
        
        // Active memberships
        $sql = "SELECT COUNT(*) as active_memberships FROM users 
                WHERE membership_expires > CURRENT_TIMESTAMP";
        $result = $this->db->fetch($sql);
        $stats['active_memberships'] = $result['active_memberships'] ?? 0;
        
        return $stats;
    }
    
    /**
     * Get recent payments for admin dashboard
     */
    public function getRecentPayments($limit = 10) {
        $sql = "SELECT p.*, u.name as user_name, u.email as user_email 
                FROM payments p 
                JOIN users u ON p.user_id = u.id 
                ORDER BY p.created_at DESC 
                LIMIT :limit";
        return $this->db->fetchAll($sql, [':limit' => $limit]);
    }
}