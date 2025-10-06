# ConnectHub### ✅ **Currently Available**
- **User Authentication & Authorization** - Secure registration, login with role-based access
- **Advanced Group Management** - Complete group system with ownership hierarchy
  - Owner/Co-Host/Moderator/Member role management
  - Group creation, joining, and management with permissions
  - Visual role indicators and promotion/demotion system
- **Event Management System** - Full event creation and management
  - Event creation with cover image upload
  - Location types: In-person, Online, Hybrid
  - RSVP system architecture with attendee tracking
  - Role-based event creation permissions
- **Payment System** - Stripe integration for membership payments (test mode complete)
- **Database Integration** - PostgreSQL with secure connection handling
- **Responsive Design** - Mobile-first, desktop-optimized interface
- **Flash Messaging** - User feedback system with elegant notifications

### 🔄 **In Development**
- **Event RSVP Interface** - User-facing RSVP and event browsing
- **User Profiles** - Comprehensive profile management and customization
- **Admin Dashboard** - Complete administrative control panel
- **Email Notifications** - Automated event reminders and updatesn, community-driven platform inspired by Meetup.com, built with PHP and designed for secure, professional event management and group networking.

![ConnectHub](https://img.shields.io/badge/Status-In%20Development-yellow)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-blue)
![PostgreSQL](https://img.shields.io/badge/Database-PostgreSQL-blue)
![Bootstrap](https://img.shields.io/badge/Frontend-Bootstrap%205-purple)
![Stripe](https://img.shields.io/badge/Payments-Stripe-green)

## 🚀 Features

### ✅ **Currently Available**
- **User Authentication & Authorization** - Secure registration, login with role-based access
- **Payment System** - Stripe integration for membership payments (test mode complete)
- **Role Management** - Member, Organizer, Admin, Super Admin hierarchy
- **Responsive Design** - Mobile-first, desktop-optimized interface
- **Flash Messaging** - User feedback system with elegant notifications
- **Database Integration** - PostgreSQL with secure connection handling

### � **In Development**
- **Group Management** - Create, join, and manage community groups
- **Event System** - Full event creation, RSVP, and calendar integration
- **User Profiles** - Comprehensive profile management and customization
- **Admin Dashboard** - Complete administrative control panel
- **Email Notifications** - Automated event reminders and updates

### 📅 **Planned Features**
- **Advanced Search** - Smart filtering for groups and events
- **Social Features** - User connections and networking tools
- **Mobile App** - Native iOS and Android applications
- **API Integration** - Third-party calendar and social media sync

## 🛠️ Tech Stack

- **Backend**: PHP 8.0+, MVC Architecture
- **Database**: PostgreSQL with role-based security
- **Frontend**: Bootstrap 5, JavaScript ES6+, Responsive Design
- **Payments**: Stripe API with secure webhook integration
- **Security**: CSRF protection, SQL injection prevention, XSS protection
- **Hosting**: Optimized for GoDaddy shared hosting

## 📊 Development Progress

**Overall: ~65% Complete**

- ✅ **Foundation & Setup** (100%)
- ✅ **Payment System** (90% - Test mode complete)
- ✅ **User Management** (75% - Auth complete, profiles pending)
- ✅ **Group Management** (95% - Complete with advanced role hierarchy)
- ✅ **Event Management** (85% - Creation and backend complete, RSVP UI pending)
- ✅ **UI/UX** (85% - Responsive design complete, event interfaces done)
- ❌ **Administration** (25% - Basic user management done)
- ❌ **Communication** (0% - Planned)

## � Getting Started

### Prerequisites
- PHP 8.0 or higher
- PostgreSQL 12+
- Composer (optional, for future dependencies)
- Stripe account (for payments)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/connecthub.git
   cd connecthub
   ```

2. **Set up the database**
   ```sql
   -- Create database and user
   CREATE DATABASE connecthub;
   CREATE USER connecthub_admin WITH PASSWORD 'your_password';
   GRANT ALL PRIVILEGES ON DATABASE connecthub TO connecthub_admin;
   ```

3. **Configure the application**
   ```bash
   cp config/production_config_TEMPLATE.php config/local_config.php
   ```
   - Update database credentials
   - Add your Stripe API keys
   - Configure SMTP settings

4. **Initialize the database**
   - Visit `http://yourdomain.com/add-payments-table.php` to create payment tables
   - Create your first admin user through registration

5. **Set up web server**
   - Point document root to `/public` directory
   - Ensure URL rewriting is enabled

### Development Setup

For local development:
```bash
# Start local server (if using PHP built-in server)
php -S localhost:8000 -t public/

# Or configure your preferred web server (Apache/Nginx)
```

## 🔧 Configuration

### Database Configuration
```php
<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'connecthub');
define('DB_USER', 'connecthub_admin');
define('DB_PASS', 'your_secure_password');
define('DB_PORT', '5432');

// Stripe Configuration (Test Mode)
define('STRIPE_PUBLIC_KEY', 'pk_test_...');
define('STRIPE_SECRET_KEY', 'sk_test_...');
?>
```

## 👥 Default Accounts

After running the sample data:

| Role | Email | Password | Purpose |
|------|-------|----------|---------|
| Super Admin | admin@connecthub.com | admin123 | System administration |
| Organizer | john@example.com | password | Testing organizer features |
| Member | jane@example.com | password | Testing member features |

**⚠️ Important**: Change these passwords immediately in production!

## 📁 Project Structure

```
connecthub/
├── config/                 # Configuration files
│   ├── constants.php      # Application constants
│   ├── database.php       # Database connection
│   └── bootstrap.php      # App initialization
├── src/                   # Application source code
│   ├── controllers/       # Request handlers
│   ├── models/           # Data models
│   ├── views/            # Templates
│   └── helpers/          # Utility functions
├── public/               # Web-accessible files
│   ├── assets/          # CSS, JS, images
│   └── uploads/         # User uploaded files
├── database/            # Database migrations and seeds
├── docs/               # Documentation
└── README.md
```

## 🔒 Security Features

- **Authentication**: Secure login with bcrypt password hashing
- **Authorization**: Role-based access control
- **CSRF Protection**: Cross-site request forgery prevention
- **XSS Prevention**: Output sanitization and CSP headers
- **SQL Injection**: Prepared statements and parameterized queries
- **Rate Limiting**: Login attempt limiting with account lockout
- **Session Security**: Secure session configuration
- **File Upload**: Secure file handling with type validation

## 🚀 Deployment

### Production Checklist

- [ ] Set `APP_ENV` to `production`
- [ ] Disable `APP_DEBUG`
- [ ] Use HTTPS with valid SSL certificate
- [ ] Configure secure database credentials
- [ ] Set up proper file permissions
- [ ] Configure backup strategy
- [ ] Set up monitoring and logging
- [ ] Test payment integration
- [ ] Configure email delivery
- [ ] Set up CRON jobs (if needed)

### GoDaddy Deployment

1. Upload files to your hosting directory
2. Import database via cPanel
3. Update configuration for your domain
4. Set up SSL certificate
5. Configure email settings

## 🔄 Development

### Local Development Setup

1. Use XAMPP/WAMP/MAMP for local PHP/MySQL
2. Copy `local_config.template.php` to `local_config.php`
3. Update local configuration
4. Import sample data for testing

### Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📞 Support

For issues and questions:
- Check the [documentation](docs/)
- Review [common issues](docs/TROUBLESHOOTING.md)
- Open an issue on GitHub

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

Built with modern PHP practices and security best practices in mind. Designed for scalability and future mobile app integration.

---

**🔗 Links**
- [Documentation](docs/)
- [API Documentation](docs/API.md) (Coming Soon)
- [Development Guide](docs/DEVELOPMENT.md)
- [Security Guide](docs/SECURITY.md)