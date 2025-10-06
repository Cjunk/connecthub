# ConnectHub - Local Development vs Production Deployment

## 🚀 **IMPORTANT: Configuration Setup**

### **For Local Development:**
- ✅ Use the current setup - `config/local_config.php` is automatically loaded
- ✅ URLs point to `http://localhost/connecthub/public`
- ✅ Database uses local settings (root/empty password)
- ✅ Debug mode enabled

### **For Production Deployment to GoDaddy:**

1. **Copy production config:**
   ```
   Copy: deployment/production_config.php 
   To: production_config.php (root folder)
   ```

2. **Update production_config.php with your actual values:**
   - Database credentials from GoDaddy
   - Your actual domain URL
   - Email settings
   - Stripe live keys
   - Generate new security keys

3. **Upload files excluding:**
   - `config/local_config.php` (keep this local only)
   - `test_config.php`
   - `.git` folder
   - This README file

## 🔧 **Current Configuration:**

### **Local Development (Active):**
- **BASE_URL:** `http://localhost/connecthub/public`
- **Environment:** Development
- **Debug:** Enabled
- **Database:** Local (connecthub/root)

### **Production (When Deployed):**
- **BASE_URL:** `https://phat-fitness.com/connecthub/public`
- **Environment:** Production  
- **Debug:** Disabled
- **Database:** GoDaddy hosting database

## 📁 **File Structure:**
```
connecthub/
├── config/
│   ├── local_config.php     ← Used for local development
│   └── constants.php        ← Main config loader
├── deployment/
│   └── production_config.php ← Copy this to root for production
└── (rest of application files)
```

## 🔄 **How Configuration Loading Works:**

1. `constants.php` checks for `config/local_config.php` first
2. If local config exists → Uses localhost URLs (development)
3. If no local config → Looks for `production_config.php` (production)
4. If neither exists → Uses default localhost values

This ensures seamless switching between development and production!