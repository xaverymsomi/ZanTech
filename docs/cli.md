# CLI Reference

Oryn includes a CLI named `oryn`.

Preferred form:

```bash
php oryn <command>
```

Backward-compatible forms:

```bash
php oryn <command>
php zt.php <command>
```

## Commands

| Command | Purpose |
|---------|---------|
| `make:module <Name>` | Generate a controller, model, and index view under `app/Modules/<Name>`. |
| `make:module <Name> --example` | Generate a working example module with a permission guard, sample view data, and JSON status endpoint. |
| `make:controller <Name>` | Generate a controller and index view for a module. |
| `cache:clear` | Delete files from `storage/cache`. |
| `doctor` | Run lightweight environment checks without opening a database connection. |
| `test` | Run PHPUnit. |
| `db:init` | Run `src/Database/Schema/init.sql`. |
| `db:seed` | Run `src/Database/Schema/seed.sql`. |
| `db:migrate [file]` | Run one SQL file or the default RBAC/sidebar migration batch. |
| `migrate` | Run tracked pending migrations and record them in `zt_migrations`. |
| `migrate:status` | Show applied and pending tracked migrations. |
| `migrate:auto` | Generate tables from model schema arrays where supported. |
| `queue:work [--once] [--max-jobs=N] [--sleep=N]` | Start the background job queue worker. |
| `cron:run [--max-jobs=N]` | Run cron-safe lifecycle tasks once with a filesystem lock. |

## Module Scaffolding

```bash
php oryn make:module Billing
```

Creates:

```text
app/Modules/Billing/Billing.php
app/Modules/Billing/Billing_Model.php
app/Modules/Billing/Views/index.php
```

For a richer starter module:

```bash
php oryn make:module Billing --example
```

The example scaffold adds:

- `index()` with a `view_billing` permission guard.
- `status()` returning a standard JSON success response.
- A model method that supplies safe sample rows.
- A view that escapes dynamic output.

Add the matching permission row through the Permission module or a migration before exposing the module to non-admin users.

## Doctor Checks

```bash
php oryn doctor
```

The command checks PHP version, required extensions, Composer autoload, `.env`, the public front controller, storage writability, and the migrations directory. It is intentionally lightweight and does not connect to the database.

## Database Commands

```bash
php oryn db:init
php oryn db:seed
php oryn db:migrate
php oryn migrate
php oryn migrate:status
```

Run a specific SQL file:

```bash
php oryn db:migrate src/Database/migrations/20260212_create_mx_menu_table.sql
```

## Tests

```bash
php oryn test
```

The command prefers `vendor/bin/phpunit` and falls back to a global `phpunit` command.

## Cron And Queue

Use `cron:run` from the system scheduler:

```bash
* * * * * /usr/bin/php /path/to/project/oryn cron:run --max-jobs=25
```

The cron lifecycle:

- CLI-only execution.
- Creates a request ID with a `CRON-` prefix.
- Acquires `storage/cron/oryn-cron.lock` to prevent overlapping runs.
- Processes pending queue jobs once, then exits.
- Returns exit code `0` on success and `1` on failure.

For a long-running worker:

```bash
php oryn queue:work --sleep=3
```

For a deploy-safe one-shot worker:

```bash
php oryn queue:work --once
php oryn queue:work --max-jobs=10 --stop-when-empty
```
