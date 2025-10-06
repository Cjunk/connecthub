# ConnectHub - Role-Based Access System

## 🎯 **Role Hierarchy & Permissions**

### **Role Structure:**
```
Super Admin (Level 4) > Admin (Level 3) > Organizer (Level 2) > Member (Level 1)
```

### **Role Definitions:**

#### **1. Member (Default Role)**
- **Database Value:** `'member'`
- **Permissions:**
  - Browse groups and events
  - Join groups (with active membership)
  - Attend events (with active membership)
  - Update own profile
  - View dashboard
- **Restrictions:**
  - Must pay annual membership fee
  - Cannot create groups or events
  - No administrative access

#### **2. Organizer**
- **Database Value:** `'organizer'`
- **Permissions:**
  - All member permissions +
  - Create and manage groups
  - Create and manage events
  - No membership fee required
  - Access to organizer tools
- **Restrictions:**
  - Cannot access admin functions
  - Cannot manage other users

#### **3. Admin**
- **Database Value:** `'admin'`
- **Permissions:**
  - All organizer permissions +
  - User management (view, edit, suspend users)
  - Site configuration
  - Content moderation
  - Analytics and reporting
  - Admin dashboard access
- **Restrictions:**
  - Cannot access system-level configurations
  - Cannot manage super admins

#### **4. Super Admin**
- **Database Value:** `'super_admin'`
- **Permissions:**
  - Full system access
  - Database management
  - System configuration
  - Manage all user roles including admins
  - Security settings
  - API configuration

## 🗃️ **Database Schema Updates**

### **Required SQL for PostgreSQL:**
```sql
-- Add role field to existing users table
ALTER TABLE public.users 
ADD COLUMN role VARCHAR(20) DEFAULT 'member' 
CHECK (role IN ('member', 'organizer', 'admin', 'super_admin'));

-- Update existing users if needed
UPDATE public.users SET role = 'organizer' WHERE is_organizer = true;
UPDATE public.users SET role = 'member' WHERE is_organizer = false OR is_organizer IS NULL;

-- Optional: Remove old is_organizer field after migration
-- ALTER TABLE public.users DROP COLUMN is_organizer;
```

## 🔧 **PHP Implementation**

### **Helper Functions (src/helpers/functions.php):**
```php
// Check specific role
hasRole('admin')

// Check minimum role level
hasMinimumRole('organizer')

// Convenience functions
isOrganizer()    // organizer, admin, or super_admin
isAdmin()        // admin or super_admin
isSuperAdmin()   // super_admin only
```

### **Usage Examples:**
```php
// In views/templates
<?php if (isOrganizer()): ?>
    <button>Create Event</button>
<?php endif; ?>

<?php if (isAdmin()): ?>
    <a href="/admin">Admin Panel</a>
<?php endif; ?>

// In controllers
if (!hasMinimumRole('organizer')) {
    redirect('/unauthorized');
}
```

## 👥 **Test User Accounts**

| Role | Email | Password | Access Level |
|------|-------|----------|--------------|
| **Member** | jane@connecthub.local | password123 | Basic user features |
| **Member** | mike@connecthub.local | password123 | Basic user features |
| **Organizer** | john@connecthub.local | password123 | + Event/Group creation |
| **Admin** | admin@connecthub.local | admin123 | + User management |
| **Super Admin** | super@connecthub.local | super123 | Full system access |

## 🎨 **UI/UX Role-Based Features**

### **Navigation Menu Changes:**
- **Members:** Basic navigation only
- **Organizers:** + "Create Group", "Create Event" options
- **Admins:** + "Admin Panel", "User Management" options
- **Super Admins:** + "System Settings", "Database" options

### **Dashboard Differences:**
- **Members:** Personal stats, membership status
- **Organizers:** + Event management tools
- **Admins:** + Site statistics, moderation tools
- **Super Admins:** + System health, logs

## 🔒 **Security Considerations**

### **Role Validation:**
- Always validate user role on server side
- Never rely on client-side role checks for security
- Use middleware for route protection
- Log role-based actions for audit trail

### **Role Elevation:**
- Only super admins can promote to admin
- Only admins can promote to organizer
- Self-service registration creates members only
- Role changes should be logged

## 📁 **File Structure Updates**

### **Updated Files:**
- ✅ `src/models/User.php` - Role-based user creation and validation
- ✅ `src/helpers/functions.php` - Role checking functions
- ✅ `src/controllers/AuthController.php` - Role-based authentication
- ✅ `src/views/layouts/header.php` - Role-based navigation
- ✅ `public/dashboard.php` - Role-based dashboard features
- ✅ `public/add-test-users.php` - Creates users with proper roles

### **Future Files:**
- `public/admin/` - Admin-only pages
- `public/admin/users.php` - User management
- `public/admin/settings.php` - Site configuration
- `src/middleware/RoleMiddleware.php` - Route protection

## 🚀 **Deployment Notes**

### **Production Migration:**
1. Run the SQL ALTER statement to add role field
2. Update existing users based on current is_organizer field
3. Test role-based access thoroughly
4. Update production environment variables if needed

### **Rollback Plan:**
- Keep is_organizer field until fully tested
- Role field can be dropped if needed to revert
- Backup user data before migration

## 📋 **Next Development Steps**

1. **Create Admin Panel** - Dedicated admin interface
2. **User Management** - Admin tools for managing users
3. **Role-Based Middleware** - Route protection
4. **Audit Logging** - Track role-based actions
5. **Permission System** - Granular permissions within roles