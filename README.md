# ZanTech

ZanTech is a **PHP 8.2** modular web application framework: URL-driven routing, module-based MVC, session authentication, permission-aware UI, and a small **CLI (`zt`)** for scaffolding and database tasks. The front controller lives under **`public/`**; application code uses **Composer PSR-4** autoloading.

**Framework (app shell):** `2.4.1` (see `ZT_APP_VERSION` in `bootstrap/sys_pref.php`).

Repository: [github.com/xaverymsomi/ZanTech](https://github.com/xaverymsomi/ZanTech)

---

## Documentation

Full framework documentation lives in [docs/README.md](docs/README.md). A safe environment template is available at [.env.example](.env.example).

Start there for installation, project structure, request lifecycle, routing, modules, controllers, API response contracts, database usage, configuration, authentication, RBAC, views, CLI commands, testing, deployment, troubleshooting, contributing, and maintenance guidance.

---

## Versions and stack

Pinned or primary versions are the ones loaded in **`resources/views/header.php`** (CDN) and declared in **`composer.json`**. Upgrade those files together when bumping a major dependency. **Details:** [VERSIONS.md](VERSIONS.md) · **CDN lockfile:** [cdn-lock.json](cdn-lock.json).

### Runtime

| Component | Version |
|-----------|---------|
| **ZanTech / kernel** | `2.4.1` (`bootstrap/sys_pref.php`) |
| **PHP** | `^8.2` (`composer.json`) |
| **Database** | **SQL Server** (T-SQL migrations under `src/Database/migrations/`) |

### Frontend (CDN, `resources/views/header.php`)

| Library | Version |
|---------|---------|
| **Bootstrap** (CSS + JS) | `5.3.3` |
| **Font Awesome** | `6.4.2` |
| **jQuery** | `3.7.1` |
| **AngularJS** (`angular`, `angular-animate`, `angular-sanitize`) | `1.8.2` |
| **Angular UI Bootstrap** (`ui-bootstrap-tpls`) | `2.5.6` |
| **Moment.js** | `2.29.4` |
| **ng-file-upload** | `12.2.13` |
| **angular-filter** | `0.5.17` |
| **ui-select** | `0.20.0` |
| **angularjs-toaster** (script + CSS) | `3.0.0` |
| **angular-toaster** (CSS only, legacy) | `3.0.0` |

Bundled app script: **`public/assets/js/zantech.bundle.js`** (build or edit in-repo as needed; not versioned separately from the repo).

### PHP tooling (Composer)

| Tool / package | Constraint |
|----------------|------------|
| **PHPUnit** (dev) | `^11.5` |
| **vlucas/phpdotenv** | `*` |
| **Other PHP libraries** | See `composer.json` (many use `*` or caret ranges; resolve with `composer show`) |

---

## Requirements

- **PHP** `^8.2` with extensions: PDO, JSON, fileinfo, curl, SimpleXML, GD, OpenSSL (see `composer.json` for full list)
- **Composer**
- A supported **database** (the bundled migrations target **Microsoft SQL Server** / T-SQL style DDL)

---

## Quick start

1. **Clone and install dependencies**

   ```bash
   git clone https://github.com/xaverymsomi/ZanTech.git
   cd ZanTech
   composer install
   ```

2. **Environment**

   Copy or create a **`.env`** in the project root. A sample template is available at **`.env.example`**. Configuration is bootstrapped in `bootstrap/config.php` (uses `vlucas/phpdotenv`). Database and app settings are typically read via `zt_env()` and related helpers after `.env` is loaded.

3. **Database**

   ```bash
   php zt db:init      # core schema from src/Database/Schema/init.sql
   php zt db:seed      # optional seed from src/Database/Schema/seed.sql
   php zt db:migrate   # runs SQL files from src/Database/migrations/ (default RBAC/sidebar batch)
   php zt migrate      # runs pending migrations and records them in zt_migrations
   ```

   Migration scripts live in **`src/Database/migrations/`**.
   Individual file:

   ```bash
   php zt db:migrate src/Database/migrations/20260212_create_mx_menu_table.sql
   ```

4. **Web server**

   Point the document root at **`public/`** so that `public/index.php` is the entry point. Requests are forwarded through **`src/Foundation/AppLoader.php`**, which picks the runtime namespace (e.g. **web**) and loads **`src/Foundation/web.php`**.

---

## How requests flow

1. **`public/index.php`** defines `ZT_BASE_PATH` and loads **`src/Foundation/AppLoader.php`**.
2. **AppLoader** normalizes the URI, applies basic security checks, and loads the correct kernel (e.g. **`src/Foundation/web.php`**).
3. **`src/Foundation/web.php`** initializes session, logging context, and runs **`Foundation\Zantech`**.
4. **`Foundation\Zantech`** resolves the URL into **module** and **action** segments, maps slugs such as `get_all_menus` to **`getAllMenus`**, loads **`Modules\{Module}\{Module}`**, and invokes the controller method.

So a URL like `/Menu/index` maps to the **`Menu`** module’s **`index`** method on **`Modules\Menu\Menu`**.

---

## Modules (MVC)

Each feature area is a **module** under **`app/Modules/{ModuleName}/`** (PascalCase class name, e.g. `Menu` → `app/Modules/Menu/Menu.php`).

| Piece | Role |
|--------|------|
| **Controller** | Extends `Http\Controller`. Handles permissions, calls the model, sets `$this->view()->...` data, then `render()`, `renderJson()`, or response helpers such as `responseJson()`, `responseSuccess()`, and `responseError()`. |
| **Model** | Extends `Database\Model`. Encapsulates tables, queries, dropdowns, and helpers (`getRecord`, `update` / `updateRecord`, etc.). |
| **Views** | PHP templates under **`app/Modules/{Module}/Views/`** (e.g. `home.php`, `edit.php`). The view layer composes HTML or JSON payloads for Angular/legacy clients. |

Optional scaffolding:

```bash
php zt make:module YourModule
```

---

## Authentication and permissions

- **`Authentication\Auth`**, **`Authentication\Session`**, **`Authentication\CaptchaLib`**, and **`Authentication\DualControl`** handle login state, session hardening, captcha verification, and maker-checker approvals (initialized from the web kernel).
- **`Authentication\Perm_Auth`** is used in controllers to gate actions (for example `view_menu`, `add_menu`, `edit_menu` on the Menu module).
- Menu entries can align with **sections** in the database; the CLI reminds that top-level **`mx_menu.txt_name`** should match **`mx_section.txt_name`** where RBAC-driven sidebar visibility is required.

---

## HTTP responses

- **HTML pages** use `Controller::render()` and the shared layout under **`resources/views/`** (e.g. `body.php`, `header.php`).
- **JSON APIs** use `responseJson()`, `responseSuccess()`, and `responseError()` on `Http\Controller`, which return structured payloads (including `ok`, `code`, `message`, and `title`) for both modern clients and legacy Angular response handlers.

---

## CLI (`zt` / `zt.php`)

The same commands work as:

```bash
php zt <command>
php zt.php <command>
```

| Command | Purpose |
|---------|---------|
| `make:module <Name>` | Scaffold a new module |
| `make:module <Name> --example` | Scaffold a permission-aware example module with a JSON status endpoint |
| `make:controller <Name>` | Scaffold a module controller and index view |
| `cache:clear` | Clear runtime cache from `storage/cache` |
| `doctor` | Run lightweight environment checks without connecting to the database |
| `test` | Run PHPUnit tests |
| `db:init` | Run `src/Database/Schema/init.sql` |
| `db:seed` | Run `src/Database/Schema/seed.sql` |
| `db:migrate [path]` | Run one SQL file, or the default migration batch |
| `migrate` | Run pending SQL migrations and record them in `zt_migrations` |
| `migrate:status` | Show applied and pending tracked migrations |

---

## Project layout (high level)

| Path | Purpose |
|------|---------|
| `public/` | Web root (`index.php`, assets, `.htaccess` / `web.config`) |
| `src/Foundation/` | Bootstrapping, web kernel, console scaffolding, middleware, and routing |
| `src/Config/` | Dotted-key configuration repository with env and optional DB fallback |
| `src/Http/` | Request, response, and controller abstractions |
| `src/View/` | View rendering and table helper utilities |
| `src/Validation/` | Input validation |
| `src/Foundation/Routing/` | Router and route security |
| `app/Modules/` | Application modules (controllers, models, views) |
| `src/Database/` | Database access, base model, `Schema/` (init, seed), and `migrations/` (incremental SQL) |
| `src/Authentication/` | Auth, session, captcha, dual control, permissions |
| `src/Services/` | Cross-cutting application services such as hashing, validation, notification adapters, log sanitizing, and RBAC helpers |
| `src/Exceptions/` | Centralized exception handling |
| `bootstrap/` | `config.php`, `sys_pref.php`, and environment-driven bootstrap |
| `resources/views/` | Global layouts and shared templates |
| `src/helpers/` | Shared PHP helpers |
| `zt` / `zt.php` | Console entry for maintenance tasks |

---

## Frontend notes

The app uses **Bootstrap 5.3.3** and **AngularJS 1.8.2** from CDNs (see **Versions and stack**). Interactive screens (e.g. Menu management) also use **`public/assets/js/zantech.bundle.js`**. Custom styling lives in **`public/assets/css/zantech-ui.css`**.

---

## License

See [LICENSE](LICENSE) (MIT).
