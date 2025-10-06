# ConnectHub Development Setup Instructions

## Quick Start for New Developers

### 1. Initial Setup
```bash
# Clone the repository
git clone https://github.com/yourusername/connecthub.git
cd connecthub

# Copy configuration template
cp config/local_config.template.php config/local_config.php
```

### 2. Local Configuration
Edit `config/local_config.php` with your local settings:

```php
// Database - Update these for your local environment
define('DB_HOST_LOCAL', 'localhost');
define('DB_NAME_LOCAL', 'connecthub_dev');
define('DB_USER_LOCAL', 'root');
define('DB_PASS_LOCAL', '');

// URLs - Update for your local setup
define('BASE_URL_LOCAL', 'http://localhost');
define('SITE_URL_LOCAL', 'http://localhost/connecthub');

// Email - Use Mailtrap or similar for development
define('SMTP_HOST_LOCAL', 'smtp.mailtrap.io');
define('SMTP_PORT_LOCAL', 2525);
define('SMTP_USERNAME_LOCAL', 'your_mailtrap_username');
define('SMTP_PASSWORD_LOCAL', 'your_mailtrap_password');

// Payment - Use Stripe test keys
define('STRIPE_PUBLIC_KEY_LOCAL', 'pk_test_your_stripe_public_key');
define('STRIPE_SECRET_KEY_LOCAL', 'sk_test_your_stripe_secret_key');

// Security - Generate random keys for development
define('ENCRYPTION_KEY_LOCAL', 'development-encryption-key-32chars');
define('JWT_SECRET_LOCAL', 'development-jwt-secret-key-here');
```

### 3. Database Setup
```sql
-- Create development database
CREATE DATABASE connecthub_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Import schema and sample data
mysql -u root -p connecthub_dev < database/schema.sql
mysql -u root -p connecthub_dev < database/seeds.sql
```

### 4. Web Server Setup

#### Using XAMPP/WAMP
1. Copy project to `htdocs/connecthub`
2. Access via `http://localhost`

#### Using Built-in PHP Server
```bash
cd public
php -S localhost:8000
```

### 5. Test the Installation
1. Visit `http://localhost` (or `http://localhost:8000`)
2. Try logging in with test accounts:
   - Admin: admin@connecthub.com / admin123
   - Organizer: john@example.com / password
   - Member: jane@example.com / password

## Development Tools

### Recommended VS Code Extensions
- PHP Intelephense
- Auto Rename Tag
- GitLens
- Bracket Pair Colorizer
- PHP Debug

### Debugging
Add to your `local_config.php` for debugging:
```php
define('APP_DEBUG_LOCAL', true);
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Database Tools
- phpMyAdmin (included with XAMPP)
- MySQL Workbench
- TablePlus
- Sequel Pro (Mac)

## Common Development Tasks

### Adding New Features
1. Create feature branch: `git checkout -b feature/feature-name`
2. Make changes following the project structure
3. Test thoroughly
4. Commit with descriptive messages
5. Push and create pull request

### Database Changes
1. Add new migration to `database/migrations/` (create directory if needed)
2. Update `database/schema.sql` with final structure
3. Test migration on clean database

### Security Testing
```bash
# Check for security vulnerabilities in dependencies
composer audit

# Test file uploads
# Test SQL injection points
# Test XSS vulnerabilities
# Test CSRF protection
```

## Troubleshooting

### Common Issues

#### Database Connection Error
- Check database credentials in `local_config.php`
- Ensure MySQL service is running
- Verify database exists

#### File Permission Errors
```bash
# Fix upload directory permissions
chmod 755 public/uploads
chmod 644 public/uploads/.htaccess
```

#### SMTP/Email Issues
- Use Mailtrap.io for development email testing
- Check SMTP credentials
- Verify firewall isn't blocking SMTP ports

#### Session Issues
- Check PHP session configuration
- Ensure `session.save_path` is writable
- Clear browser cookies

### Debug Mode
Enable debug mode in `local_config.php`:
```php
define('APP_DEBUG_LOCAL', true);
```

This will show detailed error messages and stack traces.

## Production Deployment

### Environment Preparation
```bash
# Set production environment variables
export APP_ENV=production
export APP_DEBUG=false
export DB_HOST=your-prod-host
export DB_NAME=your-prod-database
export DB_USER=your-prod-user
export DB_PASS=your-secure-password
```

### Deployment Checklist
- [ ] Set all environment variables
- [ ] Upload files to production server
- [ ] Import database schema
- [ ] Configure web server
- [ ] Set up SSL certificate
- [ ] Test all functionality
- [ ] Monitor error logs

## Getting Help

### Documentation
- [Security Guide](SECURITY.md)
- [API Documentation](API.md) (Coming Soon)
- [Database Schema](../database/README.md)

### Contact
- Create an issue on GitHub
- Check existing issues for solutions
- Review the troubleshooting section above

Remember to never commit sensitive information like passwords or API keys to the repository!