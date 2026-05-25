# Deployment Guide

This guide covers the practical steps for deploying Oryn to a server.

## Deployment Principles

- Point the web server document root to `public/`.
- Keep `.env` outside version control and readable only by the application user.
- Run `composer install` on the server or build dependencies in a trusted release artifact.
- Make `storage/` writable by the application user.
- Disable debug output in production.
- Run tests and Composer validation before releasing.

## Server Requirements

Install:

- PHP 8.2 or newer.
- Composer.
- Required PHP extensions from `composer.json`.
- A database driver for your `DB_TYPE`, such as `pdo_sqlsrv`, `pdo_mysql`, `pdo_pgsql`, or SQLite support.
- A web server such as IIS, Apache, or Nginx.

## Release Steps

1. Fetch or upload the release.
2. Install dependencies:

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

3. Create `.env` from `.env.example`.
4. Set production values:

   ```dotenv
   APP_ENV=production
   APP_DEBUG=0
   APP_KEY=your-real-random-secret
   PASS_SALT=your-real-random-salt
   ```

5. Configure the database values.
6. Ensure writable runtime directories:

   ```text
   storage/
   logs/
   ```

7. Initialize or migrate the database as needed:

   ```bash
   php oryn db:init
   php oryn db:seed
   php oryn migrate
   ```

8. Point the web server to `public/`.
9. Restart PHP/web server services.

## Local Smoke Test

Before switching traffic, run:

```bash
composer validate --no-check-publish
php oryn test
php oryn migrate:status
```

For a quick web check:

```bash
php -S localhost:8000 -t public
```

Then visit:

```text
http://localhost:8000
```

## IIS Notes

The repository includes `public/web.config`. Configure the IIS site root to `public/`.

Check:

- PHP is registered with IIS/FastCGI.
- URL rewriting is enabled.
- The app pool identity can read the repository and write to `storage/` and `logs/`.
- Production `.env` has `APP_DEBUG=0`.

## Apache Notes

Set `DocumentRoot` to the `public/` directory.

Example:

```apache
<VirtualHost *:80>
    ServerName zantech.local
    DocumentRoot /var/www/zantech/public

    <Directory /var/www/zantech/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

If using `.htaccess`, make sure `mod_rewrite` is enabled.

## Nginx Notes

Set `root` to `public/` and route missing files to `index.php`.

Example:

```nginx
server {
    listen 80;
    server_name zantech.local;
    root /var/www/zantech/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }
}
```

## File Permissions

The web/PHP user needs:

- Read access to the repository.
- Write access to `storage/`.
- Write access to `logs/` when logging is enabled.

Avoid making the whole repository world-writable.

## Production Security Checklist

- `APP_DEBUG=0`.
- Strong `APP_KEY` and `PASS_SALT`.
- HTTPS enabled.
- Secure database credentials.
- `public/` is the only web-exposed directory.
- `.env`, `vendor/`, `src/`, `app/`, `bootstrap/`, and `storage/` are not directly exposed.
- `ZT_CSRF_ENABLED=1` when compatible with the deployed frontend.
- `ZT_RATE_LIMIT=1` when compatible with expected traffic.

## Rollback

Keep each release in a separate directory or use Git tags. To rollback:

1. Switch the symlink or checkout to the previous release.
2. Restore compatible `.env` values if needed.
3. Restart PHP/web services.
4. Confirm with a browser smoke test and logs.

