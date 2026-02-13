# Hostinger Deployment Guide

## Pre-Deployment Checklist

- [ ] Database created on Hostinger
- [ ] Environment variables configured
- [ ] Domain/subdomain set up
- [ ] SSL certificate enabled
- [ ] Files uploaded to public_html

---

## Step 1: Hostinger Setup

### 1.1 Create Database
1. Log in to Hostinger hPanel
2. Go to **Databases** → **MySQL Databases**
3. Create new database:
   - Database name: `construction_erp`
   - Username: `erp_user`
   - Password: (strong password)
4. Save credentials for `.env` file

### 1.2 Domain Configuration
1. Go to **Domains** → **Subdomains** (if using subdomain)
2. Create subdomain for multi-tenancy (e.g., `*.yourdomain.com`)
3. Enable wildcard subdomain support

---

## Step 2: Upload Files

### 2.1 Directory Structure
```
public_html/
├── .htaccess          (from public/.htaccess)
├── index.php          (from public/index.php - MODIFIED)
├── assets/            (from public/assets/)
│   ├── css/
│   └── js/
├── app/               (move to parent or keep here)
├── config/
├── database/
├── routes/
├── vendor/
└── views/
```

### 2.2 Upload Methods
**Option A: File Manager**
1. Go to hPanel → Files → File Manager
2. Navigate to `public_html`
3. Upload zip file and extract

**Option B: FTP**
1. Go to hPanel → Files → FTP Accounts
2. Create FTP account
3. Use FileZilla or similar client
4. Upload all files

**Option C: Git (Recommended)**
1. SSH into server
2. Clone repository directly

---

## Step 3: Configure Environment

### 3.1 Create `.env` file in root directory

```env
# Application
APP_NAME="Construction ERP"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database (from Hostinger)
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456789_erp
DB_USERNAME=u123456789_user
DB_PASSWORD=your_secure_password

# JWT (generate with: openssl rand -hex 32)
JWT_SECRET=your_64_character_hex_string_here
JWT_EXPIRY=86400

# Stripe
STRIPE_PUBLIC_KEY=pk_live_xxxx
STRIPE_SECRET_KEY=sk_live_xxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxx

# Mail (Hostinger SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Construction ERP"
```

### 3.2 Set File Permissions
```bash
chmod 755 public_html
chmod 644 public_html/.htaccess
chmod 600 .env
chmod -R 755 app/ config/ routes/ views/
chmod -R 777 storage/  # if using file storage
```

---

## Step 4: Run Database Migration

### 4.1 Using phpMyAdmin
1. Go to hPanel → Databases → phpMyAdmin
2. Select your database
3. Click **Import**
4. Upload `database/migrations/001_create_tables.sql`
5. Execute

### 4.2 Verify Tables
Check that all 20+ tables were created successfully.

---

## Step 5: Security Configuration

### 5.1 .htaccess (already configured)
The `public/.htaccess` file includes:
- HTTPS redirect
- URL rewriting
- Security headers
- Directory protection

### 5.2 Enable SSL
1. Go to hPanel → SSL/TLS
2. Enable **Free SSL** (Let's Encrypt)
3. Wait for certificate provisioning (up to 24 hours)

### 5.3 Additional Security
- Enable **Hotlink Protection**
- Enable **Directory Privacy** for sensitive folders
- Set up **Backup** schedule

---

## Step 6: PHP Settings

### 6.1 Recommended PHP Configuration
Go to hPanel → Advanced → PHP Configuration:

```ini
php_version = 8.1 or higher
memory_limit = 256M
max_execution_time = 300
upload_max_filesize = 50M
post_max_size = 50M
display_errors = Off
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
```

---

## Step 7: Post-Deployment

### 7.1 Test Application
1. Visit `https://yourdomain.com`
2. Test login/registration
3. Create test tenant (subdomain)
4. Verify API endpoints at `/api/...`

### 7.2 Create Admin User
```sql
-- Run in phpMyAdmin
INSERT INTO users (tenant_id, email, password, first_name, last_name, role_id, status, created_at)
VALUES (1, 'admin@yourdomain.com', '$2y$10$...hashed_password...', 'Admin', 'User', 1, 'active', NOW());
```

### 7.3 Set Up Cron Jobs (Optional)
Go to hPanel → Advanced → Cron Jobs:

```bash
# Daily backup reminder
0 0 * * * curl -s https://yourdomain.com/api/cron/daily > /dev/null

# Hourly low stock alerts
0 * * * * curl -s https://yourdomain.com/api/cron/inventory-alerts > /dev/null
```

---

## Troubleshooting

### Common Issues

**500 Internal Server Error**
- Check `.htaccess` syntax
- Verify PHP version compatibility
- Check file permissions
- View error logs in hPanel

**Database Connection Failed**
- Verify DB credentials in `.env`
- Check if database exists
- Confirm user has proper permissions

**Blank Page**
- Enable `APP_DEBUG=true` temporarily
- Check PHP error logs
- Verify `index.php` path modifications

**API Not Working**
- Check CORS settings
- Verify route registration
- Test with browser dev tools

---

## Maintenance

### Regular Tasks
- Weekly: Review activity logs
- Monthly: Database backup
- Quarterly: Update dependencies
- Annually: SSL certificate renewal (auto)

### Backup Strategy
1. Enable automatic daily backups in hPanel
2. Download weekly backups locally
3. Store monthly backups in cloud storage
