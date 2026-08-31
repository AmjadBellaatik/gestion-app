# Deploying to a brand-new server

This application ships with a WordPress-style first-run installer. On a fresh
server you do **not** edit `.env` or seed the database by hand — you open the
site in a browser and follow the wizard.

---

## 1. Requirements

| Item | Minimum |
|------|---------|
| PHP | see `composer.json` → `require.php` (currently **8.3+**; the installed `vendor/` may require 8.4+) |
| PHP extensions | `pdo_mysql mbstring openssl tokenizer xml ctype json fileinfo bcmath curl dom` |
| Database | MySQL 8+ / MariaDB 10.6+ (PostgreSQL and SQLite also work) |
| Web server | Apache or Nginx with the document root set to **`public/`** |
| Composer | 2.x |

---

## 2. Install the code

```bash
git clone <your-remote> gestion-app
cd gestion-app
cp .env.example .env
composer install --no-dev --optimize-autoloader
```

Do **not** run `php artisan key:generate` or `php artisan migrate` — the
installer does both.

---

## 3. Permissions

```bash
# writable by the web-server user (www-data / nginx / apache)
chmod -R ug+rw storage bootstrap/cache
# make sure .env is writable by that same user (the installer writes to it)
chmod ug+rw .env
```

---

## 4. Point the document root at `public/`

**The Laravel project root must never be web-served.** Only `public/` may be
exposed.

Apache (vhost):

```apache
<VirtualHost *:443>
    ServerName erp.example.com
    DocumentRoot /var/www/gestion-app/public

    <Directory /var/www/gestion-app/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Nginx:

```nginx
server {
    listen 443 ssl;
    server_name erp.example.com;
    root /var/www/gestion-app/public;
    index index.php;

    location / { try_files $uri $uri/ /index.php?$query_string; }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    # never serve dotfiles / the project internals
    location ~ /\.(?!well-known) { deny all; }
}
```

> A bundled project-root `.htaccess` denies every request when **Apache**
> is accidentally pointed at the repository root. **Nginx ignores
> `.htaccess`** — there you must set the document root correctly. Laravel
> cannot protect a server that bypasses `public/index.php`.

---

## 5. Run the installer

1. Browse to `https://erp.example.com/`.
2. You are redirected to `/install`. Follow the seven steps:

   | Step | What it does |
   |------|--------------|
   | Requirements | checks PHP version, extensions, writable paths |
   | Database | you enter DB host / name / user / password → **Test connection** → saved to `.env` |
   | Application | app name, public URL, default language (fr / en / ar) |
   | Initialize | runs every migration + creates roles, permissions, settings |
   | Company | your first company (multi-company ERP) |
   | Administrator | the first **Super Admin** (password hashed, 12+ chars) |
   | Finish | generates `APP_KEY` if missing, sets `APP_ENV=production` / `APP_DEBUG=false`, rebuilds caches, writes the lock file |

3. You are redirected to `/admin/login`. Sign in with the admin you just
   created.

After this, `/install` returns **404** and can never be re-run.

> `php artisan config:cache` is executed for you at the end. If you later edit
> `.env`, run `php artisan config:clear` (or `config:cache` again).
> `route:cache` is intentionally **not** run — `routes/web.php` contains
> closure routes that cannot be serialized.

If a step fails (e.g. wrong DB password) the wizard shows a controlled retry
screen. Nothing is locked until the final step succeeds, so you can safely
retry — roles, the company and the admin are created idempotently and never
duplicated.

---

## 6. Upgrading an existing server (already installed before this release)

Pulling this code onto a server that **already has** a configured `.env`, a
migrated database and users will **not** open the installer:

* the upgrade migration `2026_09_01_000000_bootstrap_installation_lock`
  writes the lock file automatically the first time you run
  `php artisan migrate`;
* or run it explicitly, without creating any data:

  ```bash
  php artisan app:mark-installed
  ```

Check state at any time:

```bash
php artisan app:install-status
```

---

## 7. Post-install cron

```cron
* * * * * cd /var/www/gestion-app && php artisan schedule:run >> /dev/null 2>&1
```
