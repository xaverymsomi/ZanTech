# CLI Reference

ZanTech includes a CLI named `zt`.

Both forms work:

```bash
php zt <command>
php zt.php <command>
```

## Commands

| Command | Purpose |
|---------|---------|
| `make:module <Name>` | Generate a controller, model, and index view under `app/Modules/<Name>`. |
| `make:controller <Name>` | Generate a controller and index view for a module. |
| `cache:clear` | Delete files from `storage/cache`. |
| `test` | Run PHPUnit. |
| `db:init` | Run `src/Database/Schema/init.sql`. |
| `db:seed` | Run `src/Database/Schema/seed.sql`. |
| `db:migrate [file]` | Run one SQL file or the default RBAC/sidebar migration batch. |
| `migrate` | Run tracked pending migrations and record them in `zt_migrations`. |
| `migrate:status` | Show applied and pending tracked migrations. |
| `migrate:auto` | Generate tables from model schema arrays where supported. |
| `queue:work` | Start the background job queue worker. |

## Module Scaffolding

```bash
php zt make:module Billing
```

Creates:

```text
app/Modules/Billing/Billing.php
app/Modules/Billing/Billing_Model.php
app/Modules/Billing/Views/index.php
```

## Database Commands

Initialize:

```bash
php zt db:init
```

Seed:

```bash
php zt db:seed
```

Run default migration batch:

```bash
php zt db:migrate
```

Run a specific file:

```bash
php zt db:migrate src/Database/migrations/20260212_create_mx_menu_table.sql
```

Tracked migrations:

```bash
php zt migrate
php zt migrate:status
```

## Tests

```bash
php zt test
```

The command prefers `vendor/bin/phpunit` and falls back to a global `phpunit` command.

