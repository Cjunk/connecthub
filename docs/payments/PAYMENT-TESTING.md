# 💳 Payment System Testing Checklist

*Comprehensive testing guide for ConnectHub payment system*

---

## 🧪 **BASIC PAYMENT TESTING**

### ✅ **Test Successful Payments**
- ❌ **Test Card**: `4242 4242 4242 4242` (Always succeeds)
  - [ ] Use expiry: `12/25` (any future date)
  - [ ] Use CVC: `123` (any 3 digits)
  - [ ] Verify payment goes through
  - [ ] Check database for payment record
  - [ ] Verify membership activation
  - [ ] Check Stripe dashboard for transaction

### ✅ **Test Failed Payments**
- ❌ **Declined Card**: `4000 0000 0000 0002` (Always declined)
  - [ ] Verify error message displays
  - [ ] Check no payment record created
  - [ ] Verify membership NOT activated
  - [ ] Test user can try again

- ❌ **Insufficient Funds**: `4000 0000 0000 9995`
  - [ ] Test proper error handling
  - [ ] Verify no charge processed

- ❌ **Expired Card**: `4000 0000 0000 0069` 
  - [ ] Test expired card handling
  - [ ] Verify proper error message

### ✅ **Test Authentication Required**
- ❌ **3D Secure Card**: `4000 0025 0000 3155`
  - [ ] Test authentication popup
  - [ ] Complete authentication flow
  - [ ] Verify payment after authentication

---

## 📅 **MEMBERSHIP EXPIRY TESTING**

### ✅ **Test Membership Logic**
- ❌ **New User Flow**:
  - [ ] Create new user account
  - [ ] Verify they need to pay membership
  - [ ] Complete payment
  - [ ] Check membership_expires date is set (12 months from now)
  - [ ] Verify user has access to member features

- ❌ **Existing Member Flow**:
  - [ ] User with valid membership tries to pay again
  - [ ] Should be redirected with "membership already active" message
  - [ ] Verify no duplicate payment

- ❌ **Expired Member Flow**:
  - [ ] Manually set membership_expires to past date in database
  - [ ] User should be prompted to renew
  - [ ] Complete payment
  - [ ] Verify new expiry date is set

### ✅ **Test Different User Roles**
- ❌ **Member Role**: Should need to pay membership
- ❌ **Organizer Role**: Should need to pay membership  
- ❌ **Admin Role**: Should be exempt from payment
- ❌ **Super Admin Role**: Should be exempt from payment

---

## 🔍 **DATABASE VERIFICATION**

### ✅ **Check Payment Records**
```sql
-- Run these queries in your PostgreSQL database to verify

-- 1. Check all payments
SELECT * FROM payments ORDER BY created_at DESC;

-- 2. Check payment for specific user
SELECT * FROM payments WHERE user_id = [USER_ID];

-- 3. Check user membership status
SELECT id, email, role, membership_expires FROM users WHERE id = [USER_ID];

-- 4. Check users with active memberships
SELECT * FROM users WHERE membership_expires > CURRENT_TIMESTAMP;

-- 5. Check expired memberships
SELECT * FROM users WHERE membership_expires < CURRENT_TIMESTAMP;
```

### ✅ **Verify Payment Data Integrity**
- ❌ **Check Required Fields**:
  - [ ] user_id matches logged-in user
  - [ ] amount is correct ($50.00)
  - [ ] currency is 'USD'
  - [ ] type is 'membership'
  - [ ] status is 'completed' for successful payments
  - [ ] stripe_payment_intent_id is populated
  - [ ] created_at timestamp is correct

---

## 🌐 **STRIPE DASHBOARD VERIFICATION**

### ✅ **Check Stripe Test Dashboard**
1. **Go to**: https://dashboard.stripe.com/test/payments
2. **Verify Each Payment**:
   - [ ] Payment amount matches ($50.00)
   - [ ] Payment status is "Succeeded"
   - [ ] Customer email matches user
   - [ ] Payment method is correct
   - [ ] Metadata includes user_id and payment_type

3. **Check Payment Intent Details**:
   - [ ] Payment Intent ID matches database
   - [ ] Amount is in cents (5000 = $50.00)
   - [ ] Currency is 'usd'
   - [ ] Status is 'succeeded'

---

## 🔄 **EDGE CASE TESTING**

### ✅ **Test Unusual Scenarios**
- ❌ **Multiple Browser Tabs**:
  - [ ] Open payment page in 2 tabs
  - [ ] Complete payment in one tab
  - [ ] Try to pay in other tab (should prevent double payment)

- ❌ **Payment Interruption**:
  - [ ] Start payment process
  - [ ] Close browser before completion
  - [ ] Check no partial payments created

- ❌ **Network Issues**:
  - [ ] Simulate slow connection
  - [ ] Test payment timeout handling

- ❌ **JavaScript Disabled**:
  - [ ] Disable JavaScript in browser
  - [ ] Test payment form still shows error message

### ✅ **Test User Experience**
- ❌ **Payment Success Flow**:
  - [ ] User completes payment
  - [ ] Redirected to success page
  - [ ] Success message displays
  - [ ] User can access dashboard
  - [ ] Membership status shows "Active"

- ❌ **Payment Failure Flow**:
  - [ ] Payment fails
  - [ ] Error message is clear and helpful
  - [ ] User can try payment again
  - [ ] Form resets properly

---

## 📊 **REPORTING & MONITORING**

### ✅ **Test Payment Verification Page**
- ❌ **Visit**: `http://localhost/check-payments.php`
  - [ ] Shows payment history for current user
  - [ ] Displays correct membership status
  - [ ] Shows payment details (amount, date, status)
  - [ ] Links to Stripe dashboard work

### ✅ **Test Admin Features**
- ❌ **Login as Admin/Super Admin**:
  - [ ] Should NOT see payment requirement
  - [ ] Can access all features without membership
  - [ ] Can view other users' payment status (future feature)

---

## 🚨 **SECURITY TESTING**

### ✅ **Test Payment Security**
- ❌ **Unauthorized Access**:
  - [ ] Try to access payment endpoints while logged out
  - [ ] Should get 401 Unauthorized error

- ❌ **CSRF Protection**:
  - [ ] Try to submit payment from external site
  - [ ] Should be blocked

- ❌ **Payment Tampering**:
  - [ ] Try to modify payment amount in browser
  - [ ] Server should validate and reject

### ✅ **Test Data Validation**
- ❌ **Invalid Data**:
  - [ ] Send invalid payment amounts
  - [ ] Send malformed requests
  - [ ] Verify proper error handling

---

## 📋 **TESTING CHECKLIST SUMMARY**

### 🔥 **CRITICAL TESTS (Must Pass)**
- [ ] Successful payment with test card
- [ ] Failed payment handling
- [ ] Membership activation after payment
- [ ] Admin exemption from payments
- [ ] Database payment record creation
- [ ] Stripe dashboard transaction appears

### ⚠️ **IMPORTANT TESTS (Should Pass)**
- [ ] Payment expiry date logic
- [ ] Double payment prevention
- [ ] Different user role handling
- [ ] Error message clarity
- [ ] Payment success/failure flows

### 💡 **NICE TO HAVE TESTS (Future)**
- [ ] Payment refund functionality
- [ ] Subscription management
- [ ] Payment history export
- [ ] Automated payment reminders

---

## 🛠️ **TESTING TOOLS**

### **Test Credit Cards (Stripe Test Mode)**
```
SUCCESS: 4242 4242 4242 4242
DECLINE: 4000 0000 0000 0002
INSUFFICIENT_FUNDS: 4000 0000 0000 9995
EXPIRED_CARD: 4000 0000 0000 0069
AUTHENTICATION_REQUIRED: 4000 0025 0000 3155
```

### **Useful URLs**
- Payment Page: `http://localhost/membership.php`
- Payment Verification: `http://localhost/check-payments.php`
- Stripe Dashboard: `https://dashboard.stripe.com/test/payments`
- Payment Success: `http://localhost/payment/success.php`

---

**✅ = Ready to test**  
**❌ = Not yet tested**  

*Update this checklist as you complete each test!*