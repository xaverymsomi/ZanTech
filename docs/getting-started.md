# Getting Started

## Requirements

Oryn requires:

- PHP 8.2 or newer matching the `composer.json` constraint.
- Composer.
- PHP extensions declared in `composer.json`, including PDO, JSON, fileinfo, curl, SimpleXML, GD, OpenSSL, and other package-specific extensions.
- A supported database. The bundled schema and migrations are written for Microsoft SQL Server style SQL.

## Install

Clone the repository and install dependencies:

```bash
git clone https://github.com/xaverymsomi/Oryn.git
cd Oryn
composer install
```

Composer generates `vendor/autoload.php`, which is required by both web and CLI entry points.

## Environment

Create a `.env` file at the repository root. Start from `.env.example`.

Environment loading happens in `bootstrap/config.php` through `vlucas/phpdotenv`.

Common values include:

```dotenv
APP_ENV=local
APP_DEBUG=1
URL1=http://localhost
APP_DIR=
DB_TYPE=sqlsrv
DB_HOST=localhost
DB_NAME=oryn
DB_USER=sa
DB_PASS=secret
```

The exact database keys depend on the database code and deployment environment. Keep secrets out of version control.

## Database Setup

Initialize the core schema:

```bash
php oryn db:init
```

Optionally seed baseline data:

```bash
php oryn db:seed
```

Run the default RBAC/sidebar migration batch:

```bash
php oryn db:migrate
```

Run tracked migrations:

```bash
php oryn migrate
```

Check migration status:

```bash
php oryn migrate:status
```

## Web Server

Set the document root to `public/`.

For local PHP testing:

```bash
php -S localhost:8000 -t public
```

In production, configure Apache, Nginx, or IIS so requests enter through `public/index.php`. Do not expose the repository root as the web root.

## Verify

Run the test suite:

```bash
php oryn test
```

Expected result:

```text
OK
```
