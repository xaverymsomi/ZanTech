# Oryn

Oryn is a **PHP 8.2** modular web application framework: URL-driven routing, module-based MVC, session authentication, permission-aware UI, SQL migrations, and a small CLI for scaffolding and maintenance.

The preferred CLI entry is **`oryn`**. The older **`zt`** and **`zt.php`** entries remain as backward-compatible aliases.

**Framework shell:** `2.4.1` (see `ZT_APP_VERSION` in `bootstrap/sys_pref.php`).

Repository: [github.com/xaverymsomi/ZanTech](https://github.com/xaverymsomi/ZanTech)

## Documentation

Full framework documentation lives in [docs/README.md](docs/README.md). A safe environment template is available at [.env.example](.env.example).

## Stack

| Component | Version |
|-----------|---------|
| **Oryn / kernel** | `2.4.1` |
| **PHP** | `^8.2` |
| **Database** | SQL Server-oriented migrations, with framework support for multiple PDO drivers |
| **Frontend** | Bootstrap 5.3.3, AngularJS 1.8.2, jQuery 3.7.1 |

## Quick Start

```bash
git clone https://github.com/xaverymsomi/ZanTech.git
cd ZanTech
composer install
```

Copy `.env.example` to `.env`, then adjust database and app settings.

Initialize and migrate:

```bash
php oryn db:init
php oryn db:seed
php oryn db:migrate
php oryn migrate
```

Run checks:

```bash
php oryn doctor
php oryn test
```

## Modules

Create a basic module:

```bash
php oryn make:module Billing
```

Create a permission-aware example module:

```bash
php oryn make:module Billing --example
```

The example scaffold includes a guarded `index()` action, a JSON `status()` endpoint, a model method with sample rows, and an escaped view.

## Request Flow

1. `public/index.php` defines the base path and loads `src/Foundation/AppLoader.php`.
2. `AppLoader` normalizes the URI, loads configuration, and enters the web kernel.
3. `src/Foundation/web.php` initializes session/log context and runs the foundation kernel.
4. The kernel resolves `/Module/action` into `Modules\Module\Module::action()`.

## CLI

```bash
php oryn <command>
```

Legacy aliases:

```bash
php zt <command>
php zt.php <command>
```

Common commands:

| Command | Purpose |
|---------|---------|
| `doctor` | Run lightweight environment checks |
| `make:module <Name> [--example]` | Scaffold a module |
| `make:controller <Name>` | Scaffold a module controller |
| `cache:clear` | Clear runtime cache |
| `test` | Run PHPUnit |
| `db:init` | Initialize core schema |
| `db:seed` | Seed initial data |
| `db:migrate [file]` | Run sidebar/RBAC SQL migration batch or one SQL file |
| `migrate` | Run tracked pending migrations |
| `migrate:status` | Show migration status |
| `migrate:auto` | Generate tables from model schema arrays |
| `queue:work` | Start the background queue worker |

## Project Layout

| Path | Purpose |
|------|---------|
| `public/` | Web root and front controller |
| `src/Foundation/` | Bootstrapping, web kernel, console tooling, routing, middleware |
| `src/Database/` | Database access, models, migrations, schema |
| `src/Authentication/` | Auth, session, captcha, dual control, permissions |
| `src/Http/` | Request, response, and controller abstractions |
| `app/Modules/` | Application feature modules |
| `resources/views/` | Shared layouts |
| `bootstrap/` | Configuration and system preferences |

## Notes

Some internal class names and constants still use the historical `Zantech` / `ZT_` naming for compatibility. New user-facing docs and CLI branding use **Oryn**.

## License

See [LICENSE](LICENSE) (MIT).
