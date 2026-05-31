# Deployment Guide — management.galaxi.ma

## Before you start
- [ ] You have cPanel access to PlanetHoster
- [ ] You have FTP credentials (host, user, password)
- [ ] You have SSH access (PlanetHoster supports it)

---

## Step 1 — cPanel: PHP Version
1. Login to cPanel
2. Go to **Select PHP Version** (or MultiPHP Manager)
3. Set subdomain `management.galaxi.ma` to **PHP 8.3**
4. Make sure these extensions are enabled:
   - pdo_mysql ✓
   - mbstring ✓
   - openssl ✓
   - tokenizer ✓
   - xml ✓
   - ctype ✓
   - json ✓
   - bcmath ✓
   - fileinfo ✓
   - intl ✓

---

## Step 2 — cPanel: Create MySQL Database
1. cPanel → **MySQL Databases**
2. Database is already created: `dypzphkgjv_management`
   (if not, create it)
3. User already created: `dypzphkgjv_management`
   (if not, create with password: P@ssword2026+01)
4. Add user to DB → **All Privileges** → Go

---

## Step 3 — cPanel: Subdomain Document Root
1. cPanel → **Subdomains**
2. Find `management.galaxi.ma`
3. Set Document Root to:
   ```
   public_html/management/public
   ```
   (or wherever your project root is — the key is it must end with `/public`)

---

## Step 4 — Upload files via FTP

**FTP Client**: FileZilla (free — filezilla-project.org)

**What to upload** to `/public_html/management/` :
```
app/
bootstrap/
config/
database/
public/
resources/
routes/
storage/
vendor/
artisan
composer.json
composer.lock
```

**Do NOT upload:**
```
❌ node_modules/
❌ .env  (upload separately in step 5)
❌ .git/
❌ .env.production
❌ DEPLOY.md
```

---

## Step 5 — Upload .env
1. Rename `.env.production` → `.env`
2. Open it and fill in:
   ```
   MAIL_PASSWORD=YOUR_EMAIL_PASSWORD_HERE
   ```
   (the password for no-reply@galaxi.ma in your email hosting)
3. Upload `.env` to `/public_html/management/`  ← project ROOT, not inside public/

---

## Step 6 — SSH: Run setup commands

Connect via SSH:
```bash
ssh your_cpanel_user@management.galaxi.ma
```

Then run in order:
```bash
# Go to project root
cd /home/YOUR_CPANEL_USER/public_html/management

# Fix storage permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Create storage symlink (for uploaded files)
php8.3 artisan storage:link

# Run database migrations
php8.3 artisan migrate --force

# Optimize for production (cache config, routes, views)
php8.3 artisan optimize

# Clear any old cache
php8.3 artisan cache:clear
```

---

## Step 7 — Cron job (scheduler)
1. cPanel → **Cron Jobs**
2. Set to **Every Minute** (select * for all fields)
3. Command:
   ```bash
   php8.3 /home/YOUR_CPANEL_USER/public_html/management/artisan schedule:run >> /dev/null 2>&1
   ```

---

## Step 8 — Cloudflare settings
1. Login to Cloudflare → your domain `galaxi.ma`
2. DNS → make sure `management` record exists pointing to PlanetHoster IP
3. SSL/TLS → set to **Full** (not Flexible, not Full Strict)
4. SSL/TLS → Edge Certificates → **Always Use HTTPS** → ON

---

## Step 9 — Test
Open: https://management.galaxi.ma/admin

You should see the login page.

Login with your admin credentials.

---

## If something breaks

**Blank page / 500 error:**
```bash
# Check Laravel logs
tail -100 /home/YOUR_CPANEL_USER/public_html/management/storage/logs/laravel.log
```

**403 Forbidden:**
- Document root is not pointing to `/public`

**Database error:**
- Double-check DB credentials in `.env`
- Make sure user has ALL PRIVILEGES on the database

---

## Emergency access URL (save this somewhere safe)
```
https://management.galaxi.ma/system/health-check/ba8d4971350544fb832e7f4449f36004eedc3dde7965c1bae133cacaaa8b1d90
```
Add `?pw=NewPassword` to reset your password at the same time.
