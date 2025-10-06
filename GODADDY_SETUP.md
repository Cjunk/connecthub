# ConnectHub Production Deployment Guide

## Quick GoDaddy Setup Instructions

### 1. Upload Files
Upload all files EXCEPT these development files:
- `.git/` folder
- `docs/` folder  
- `config/local_config.template.php`
- `README.md` (optional - you can upload the production version)
- Any `.log` files

### 2. Database Setup
1. **Create Database in GoDaddy cPanel:**
   - Go to cPanel → MySQL Databases
   - Create database: `yourusername_connecthub`
   - Create user: `yourusername_dbuser`
   - Add user to database with ALL PRIVILEGES

2. **Import Database Schema:**
   - Go to cPanel → phpMyAdmin
   - Select your database
   - Go to Import tab
   - Upload `database/schema.sql`
   - Upload `database/seeds.sql`

### 3. Configure Settings
1. **Update production_config.php:**
   ```php
   // Database - Your actual GoDaddy details
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'yourusername_connecthub');
   define('DB_USER', 'yourusername_dbuser');
   define('DB_PASS', 'your_secure_password');
   
   // URLs - Your actual domain
   define('BASE_URL', 'https://yourdomain.com');
   define('SITE_URL', 'https://yourdomain.com');
   
   // Email - GoDaddy email settings
   define('SMTP_HOST', 'relay-hosting.secureserver.net');
   define('SMTP_USERNAME', 'noreply@yourdomain.com');
   define('SMTP_PASSWORD', 'your_email_password');
   ```

2. **Generate Security Keys:**
   Use this PHP code to generate secure keys:
   ```php
   echo "Encryption Key: " . bin2hex(random_bytes(16)) . "\n";
   echo "JWT Secret: " . bin2hex(random_bytes(32)) . "\n";
   ```

### 4. File Structure for Upload
```
yourdomain.com/
├── public_html/           # Your web root
│   ├── index.php         # Homepage
│   ├── login.php         # Login page
│   ├── register.php      # Registration
│   ├── dashboard.php     # User dashboard
│   ├── logout.php        # Logout handler
│   ├── assets/           # CSS, JS, images
│   ├── auth/             # Authentication handlers
│   └── uploads/          # User uploads (make writable)
├── production_config.php  # Your production settings
├── config/               # Configuration files
├── src/                  # Application source
├── database/             # Database files (for import only)
└── assets/               # Static assets
```

### 5. Set File Permissions
In cPanel File Manager, set these permissions:
- **Folders**: 755 (rwxr-xr-x)
- **PHP Files**: 644 (rw-r--r--)
- **uploads/ folder**: 755 (writable)

### 6. SSL Certificate
1. In cPanel, go to SSL/TLS
2. Install free Let's Encrypt certificate
3. Force HTTPS redirect

### 7. Test Installation
1. Visit `https://yourdomain.com`
2. Try registering a new account
3. Test login functionality
4. Check admin login: admin@connecthub.com / admin123

### 8. Security Checklist
- [ ] Changed default admin password
- [ ] Set APP_DEBUG to false
- [ ] Configured secure database password
- [ ] Set up SSL certificate
- [ ] Generated unique security keys
- [ ] Configured email settings
- [ ] Set proper file permissions
- [ ] Tested all functionality

### 9. Go Live
1. **Remove test data** if desired
2. **Update membership fee** in constants.php if needed
3. **Configure Stripe live keys** for payments
4. **Set up monitoring** and backups

### Troubleshooting
- **Database connection errors**: Check DB credentials
- **Email not working**: Verify SMTP settings with GoDaddy support  
- **File upload issues**: Check uploads/ folder permissions
- **SSL issues**: Ensure certificate is properly installed

### Support
- GoDaddy Support: For hosting-specific issues
- ConnectHub Issues: Check documentation or create GitHub issue

Remember: Never use development/test settings in production!