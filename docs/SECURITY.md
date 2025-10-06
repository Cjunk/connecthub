# Security Guide for ConnectHub

## Overview
This document outlines the security measures implemented in ConnectHub and best practices for maintaining a secure application.

## Authentication & Authorization

### Password Security
- **Hashing**: All passwords are hashed using PHP's `password_hash()` with bcrypt
- **Minimum Requirements**: 8 characters minimum length
- **Strength Validation**: Client-side password strength indicator
- **Salt**: Automatic salt generation with bcrypt

### Session Management
- **Secure Sessions**: HTTPOnly and Secure flags enabled
- **Session Timeout**: Configurable timeout (default 1 hour)
- **Session Regeneration**: ID regenerated on login
- **CSRF Protection**: Tokens generated and validated on all forms

### Login Security
- **Rate Limiting**: Maximum 5 failed attempts before lockout
- **Account Lockout**: 15-minute lockout after failed attempts
- **Login Logging**: Failed attempts are logged
- **Password Reset**: Secure token-based password reset

## Input Validation & Sanitization

### Data Sanitization
```php
// All user input is sanitized
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
```

### SQL Injection Prevention
- **Prepared Statements**: All database queries use PDO prepared statements
- **Parameter Binding**: No direct string concatenation in SQL
- **Input Validation**: Server-side validation for all inputs

### XSS Prevention
- **Output Encoding**: All output is HTML-encoded
- **CSP Headers**: Content Security Policy headers implemented
- **Input Filtering**: Dangerous HTML tags are stripped

## Database Security

### Connection Security
- **Separate User**: Dedicated database user with minimal privileges
- **SSL Connection**: Database connections over SSL (recommended)
- **Password Protection**: Strong database passwords required

### Schema Security
- **Principle of Least Privilege**: Database user has only necessary permissions
- **Foreign Key Constraints**: Maintain data integrity
- **Input Validation**: Database-level constraints where appropriate

## File Upload Security

### File Validation
```php
// Secure file upload validation
function uploadFile($file, $directory, $allowedTypes = null) {
    // File size validation
    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception('File size exceeds maximum allowed size');
    }
    
    // File type validation
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($allowedTypes && !in_array($extension, $allowedTypes)) {
        throw new Exception('File type not allowed');
    }
    
    // Generate unique filename to prevent conflicts
    $filename = uniqid() . '.' . $extension;
    
    // Store outside web root or with proper restrictions
    $filepath = $uploadPath . '/' . $filename;
    
    return $filename;
}
```

### Upload Directory Security
- **Location**: Upload directory should be outside web root when possible
- **Permissions**: Proper file permissions (no execute permissions)
- **File Type Restrictions**: Only allow specific file types
- **Size Limits**: Enforce maximum file size limits
- **Virus Scanning**: Consider implementing virus scanning for uploads

## Configuration Security

### Environment Variables
```php
// Use environment variables for sensitive data
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_USER', $_ENV['DB_USER'] ?? '');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('STRIPE_SECRET_KEY', $_ENV['STRIPE_SECRET_KEY'] ?? '');
```

### Configuration Files
- **Local Config**: Use `local_config.php` for development (not in version control)
- **Environment Separation**: Different configs for dev/staging/production
- **Secret Management**: Use environment variables or secure vaults for secrets

## HTTP Security Headers

### Recommended Headers
```php
// Security headers to implement
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' cdn.jsdelivr.net cdnjs.cloudflare.com; style-src \'self\' \'unsafe-inline\' cdn.jsdelivr.net cdnjs.cloudflare.com; img-src \'self\' data:; font-src \'self\' cdnjs.cloudflare.com');
```

## Payment Security

### PCI Compliance
- **Never Store Card Data**: Use Stripe for payment processing
- **HTTPS Only**: All payment pages must use HTTPS
- **Secure Tokens**: Use Stripe's secure tokenization
- **Environment Separation**: Different keys for test/live environments

### Stripe Integration Security
```php
// Secure Stripe configuration
$stripe = [
    'secret_key' => $_ENV['STRIPE_SECRET_KEY'],
    'publishable_key' => $_ENV['STRIPE_PUBLIC_KEY'],
];

// Validate webhook signatures
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$endpoint_secret = $_ENV['STRIPE_WEBHOOK_SECRET'];

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload, $sig_header, $endpoint_secret
    );
} catch(\UnexpectedValueException $e) {
    // Invalid payload
    http_response_code(400);
    exit();
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    http_response_code(400);
    exit();
}
```

## Email Security

### SMTP Security
- **Authentication**: Always use SMTP authentication
- **TLS/SSL**: Use encrypted connections (STARTTLS or SSL)
- **Rate Limiting**: Implement email rate limiting to prevent spam
- **SPF/DKIM**: Configure proper email authentication records

### Email Content Security
- **HTML Sanitization**: Sanitize any user-generated content in emails
- **Link Security**: Use absolute URLs with HTTPS
- **Token Security**: Use secure, time-limited tokens for email verification

## Logging & Monitoring

### Security Logging
```php
// Log security events
function logSecurityEvent($event, $details = []) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'user_id' => getCurrentUserId(),
        'event' => $event,
        'details' => $details
    ];
    
    error_log('SECURITY: ' . json_encode($logEntry));
}

// Usage examples
logSecurityEvent('login_failed', ['email' => $email]);
logSecurityEvent('account_locked', ['user_id' => $userId]);
logSecurityEvent('password_changed', ['user_id' => $userId]);
```

### Monitor These Events
- Failed login attempts
- Account lockouts
- Password changes
- Email changes
- File uploads
- Admin actions
- Database errors
- Unusual traffic patterns

## Deployment Security

### Production Checklist
- [ ] Set `APP_DEBUG` to `false`
- [ ] Set `APP_ENV` to `production`
- [ ] Use HTTPS with valid SSL certificate
- [ ] Configure secure database credentials
- [ ] Set proper file permissions (644 for files, 755 for directories)
- [ ] Remove or secure test accounts
- [ ] Configure security headers
- [ ] Set up log monitoring
- [ ] Configure backup strategy
- [ ] Test payment integration in live mode
- [ ] Set up intrusion detection/monitoring

### Server Hardening
- **PHP Configuration**: Disable dangerous functions, set secure php.ini
- **Web Server**: Configure secure virtual hosts, disable server signatures
- **Firewall**: Configure firewall to allow only necessary ports
- **Updates**: Keep all software updated
- **Backups**: Regular, tested backups stored securely

## Incident Response

### Security Breach Protocol
1. **Immediate Response**: Isolate affected systems
2. **Assessment**: Determine scope and impact
3. **Notification**: Notify affected users if personal data compromised
4. **Remediation**: Fix vulnerabilities and restore systems
5. **Review**: Conduct post-incident review and improve security

### Emergency Contacts
- **Technical Lead**: [contact information]
- **Security Team**: [contact information]
- **Legal Team**: [contact information]
- **Hosting Provider**: [contact information]

## Security Testing

### Regular Security Assessments
- **Code Reviews**: Regular security-focused code reviews
- **Penetration Testing**: Annual or bi-annual penetration testing
- **Vulnerability Scanning**: Regular automated vulnerability scans
- **Dependency Audits**: Regular checks for vulnerable dependencies

### Testing Tools
- **OWASP ZAP**: Web application security testing
- **SQLMap**: SQL injection testing
- **Nmap**: Network scanning
- **Composer Audit**: PHP dependency vulnerability checking

## Compliance & Privacy

### Data Protection
- **GDPR Compliance**: Implement data protection measures for EU users
- **Data Minimization**: Collect only necessary data
- **Data Retention**: Implement data retention policies
- **Right to Deletion**: Provide user data deletion capabilities

### Privacy Measures
- **Data Encryption**: Encrypt sensitive data at rest
- **Access Controls**: Implement role-based access controls
- **Audit Trails**: Maintain logs of data access and modifications
- **Privacy Policy**: Maintain up-to-date privacy policy

## Additional Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Guide](https://phpsec.org/)
- [Stripe Security](https://stripe.com/docs/security)
- [NIST Cybersecurity Framework](https://www.nist.gov/cyberframework)

Remember: Security is an ongoing process, not a one-time implementation. Regularly review and update security measures as the application evolves.