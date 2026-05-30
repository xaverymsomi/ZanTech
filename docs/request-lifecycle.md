# Request Lifecycle

Every web request starts in `public/index.php` and flows through the framework before reaching a module controller.

## Flow

```text
Browser
  |
  v
public/index.php
  |
  v
src/Foundation/AppLoader.php
  |
  v
bootstrap/config.php
bootstrap/sys_pref.php
src/helpers/helpers.php
  |
  v
src/Foundation/web.php
  |
  v
Foundation\\Oryn
  |
  v
Middleware pipeline
  |
  v
Modules\{Module}\{Module}::{method}()
  |
  v
View or Response
```

## Entry Point

`public/index.php` defines `ZT_BASE_PATH`, starts the optional profiler, and loads `src/Foundation/AppLoader.php`.

The web server should route all application requests to this file.

## App Loader

`src/Foundation/AppLoader.php` performs early boot work:

- Loads Composer autoload.
- Registers the exception handler.
- Loads the custom error handler.
- Loads configuration, constants, and helpers.
- Normalizes the URI.
- Detects the namespace: `web`, `api`, or `cronjob`.
- Resolves the matching kernel file.

## Web Kernel

`src/Foundation/web.php` starts output buffering, initializes secure sessions for web requests, assigns a request ID, and runs the legacy-compatible `Foundation\\Oryn` kernel class.

## Cron Lifecycle

Cron jobs should use:

```bash
php oryn cron:run --max-jobs=25
```

The cron runner is CLI-only, assigns a `CRON-` request ID, takes a non-blocking lock at `storage/cron/oryn-cron.lock`, processes pending queue jobs once, prints start/finish timing, and exits with a meaningful status code. This keeps scheduled jobs from overlapping when a previous run is still active.

## Application Dispatcher

`Foundation\\Oryn` captures the request, creates a route context, runs middleware, resolves the controller, and calls the matching action.

For example:

```text
/Menu/index
```

maps to:

```php
Modules\Menu\Menu::index()
```

## Response

Controllers can return:

- Full HTML layout through `render()`.
- A direct template through `renderFull()`.
- JSON through `responseJson()`, `responseSuccess()`, or `responseError()`.
- Redirect responses through `responseRedirect()`.

