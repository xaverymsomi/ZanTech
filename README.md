# ZanTech

ZanTech is a **PHP 8.2** modular web application framework: URL-driven routing, module-based MVC, session authentication, permission-aware UI, and a small **CLI (`zt`)** for scaffolding and database tasks. The front controller lives under **`public/`**; application code uses **Composer PSR-4** autoloading.

**Framework (app shell):** `1.0.3` (see `ZT_APP_VERSION` in `constants/sys_pref.php`).

Repository: [github.com/xaverymsomi/ZanTech](https://github.com/xaverymsomi/ZanTech)

---

## Versions and stack

Pinned or primary versions are the ones loaded in **`views/header.php`** (CDN) and declared in **`composer.json`**. Upgrade those files together when bumping a major dependency. **Details:** [VERSIONS.md](VERSIONS.md) · **CDN lockfile:** [cdn-lock.json](cdn-lock.json).

### Runtime

| Component | Version |
|-----------|---------|
| **ZanTech / kernel** | `1.0.3` (`constants/sys_pref.php`) |
| **PHP** | `^8.2` (`composer.json`) |
| **Database** | **SQL Server** (T-SQL migrations under `Database/migrations/`) |

### Frontend (CDN, `views/header.php`)

| Library | Version |
|---------|---------|
| **Bootstrap** (CSS + JS) | `5.3.2` |
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

`composer.json` still lists **`twbs/bootstrap` `3.3.*`** for legacy paths; the **main UI** uses **Bootstrap 5.3.2** from the CDN above.

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

   Copy or create a **`.env`** in the project root. Configuration is bootstrapped in `configuration/config.php` (uses `vlucas/phpdotenv`). Database and app settings are typically read via `zt_env()` and related helpers after `.env` is loaded.

3. **Database**

   ```bash
   php zt db:init      # core schema from Database/Schema/init.sql
   php zt db:seed      # optional seed from Database/Schema/seed.sql
   php zt db:migrate   # runs SQL files from Database/migrations/ (default RBAC/sidebar batch)
   php zt migrate      # runs pending migrations and records them in zt_migrations
   ```

   Migration scripts live in **`Database/migrations/`**.
   Individual file:

   ```bash
   php zt db:migrate Database/migrations/20260212_create_mx_menu_table.sql
   ```

4. **Web server**

   Point the document root at **`public/`** so that `public/index.php` is the entry point. Requests are forwarded through **`Foundation/AppLoader.php`**, which picks the runtime namespace (e.g. **web**) and loads **`Foundation/web.php`**.

---

## How requests flow

1. **`public/index.php`** defines `ZT_BASE_PATH` and loads **`Foundation/AppLoader.php`**.
2. **AppLoader** normalizes the URI, applies basic security checks, and loads the correct kernel (e.g. **`Foundation/web.php`**).
3. **`Foundation/web.php`** initializes session, logging context, and runs **`Library\Zantech`**.
4. **`Library\Zantech`** resolves the URL into **module** and **action** segments, maps slugs such as `get_all_menus` to **`getAllMenus`**, loads **`Modules\{Module}\{Module}`**, and invokes the controller method.

So a URL like `/Menu/index` maps to the **`Menu`** module’s **`index`** method on **`Modules\Menu\Menu`**.

---

## Modules (MVC)

Each feature area is a **module** under **`Modules/{ModuleName}/`** (PascalCase class name, e.g. `Menu` → `Modules/Menu/Menu.php`).

| Piece | Role |
|--------|------|
| **Controller** | Extends `Http\Controller`. Handles permissions, calls the model, sets `$this->view()->...` data, then `render()`, `renderJson()`, or response helpers such as `responseJson()`, `responseSuccess()`, and `responseError()`. |
| **Model** | Extends `Library\Model`. Encapsulates tables, queries, dropdowns, and helpers (`getRecord`, `update` / `updateRecord`, etc.). |
| **Views** | PHP templates under **`Modules/{Module}/Views/`** (e.g. `home.php`, `edit.php`). The view layer composes HTML or JSON payloads for Angular/legacy clients. |

Optional scaffolding:

```bash
php zt make:module YourModule
```

---

## Authentication and permissions

- **`Authentication\Auth`** and **`Authentication\Session`** handle login state and session hardening (initialized from the web kernel).
- **`Authentication\Perm_Auth`** is used in controllers to gate actions (for example `view_menu`, `add_menu`, `edit_menu` on the Menu module).
- Menu entries can align with **sections** in the database; the CLI reminds that top-level **`mx_menu.txt_name`** should match **`mx_section.txt_name`** where RBAC-driven sidebar visibility is required.

---

## HTTP responses

- **HTML pages** use `Controller::render()` and the shared layout under **`views/`** (e.g. `body.php`, `header.php`).
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
| `db:init` | Run `Database/Schema/init.sql` |
| `db:seed` | Run `Database/Schema/seed.sql` |
| `db:migrate [path]` | Run one SQL file, or the default migration batch |
| `migrate` | Run pending SQL migrations and record them in `zt_migrations` |
| `migrate:status` | Show applied and pending tracked migrations |

---

## Project layout (high level)

| Path | Purpose |
|------|---------|
| `public/` | Web root (`index.php`, assets, `.htaccess` / `web.config`) |
| `Foundation/` | Bootstrapping: `AppLoader.php`, `web.php`, `cronjob.php` |
| `Config/` | Dotted-key configuration repository with env and optional DB fallback |
| `Library/` | Remaining legacy/core services such as `Zantech`, `Model`, `DataView`, and `DualControl` while migration continues. |
| `Http/` | Request, response, and controller abstractions |
| `View/` | View rendering |
| `Validation/` | Input validation |
| `Foundation/Routing/` | Router and route security |
| `Modules/` | Application modules (controllers, models, views) |
| `Database/` | `Schema/` (init, seed), `migrations/` (incremental SQL) |
| `Authentication/` | Auth, session, permissions |
| `Exceptions/` | Centralized exception handling |
| `configuration/` | `config.php` and environment-driven bootstrap |
| `constants/` | System preferences and constants |
| `views/` | Global layouts and shared templates |
| `helpers/` | Shared PHP helpers |
| `zt` / `zt.php` | Console entry for maintenance tasks |

---

## Frontend notes

The app uses **Bootstrap 5.3.2** and **AngularJS 1.8.2** from CDNs (see **Versions and stack**). Interactive screens (e.g. Menu management) also use **`public/assets/js/zantech.bundle.js`**. Custom styling lives in **`public/assets/css/zantech-ui.css`**.

---

## License

See [LICENSE](LICENSE) (MIT).
