# Configuration

Configuration is split between environment loading, framework constants, and runtime setting lookup.

## Environment Loading

`bootstrap/config.php` loads `.env` using `vlucas/phpdotenv`.

It also defines:

```php
zt_env(string $key, $default = null): string
```

Use `zt_env()` for environment values that must be available during early boot.

## Global Constants

`bootstrap/sys_pref.php` defines framework-wide constants.

Examples:

| Constant | Purpose |
|----------|---------|
| `ZT_APP_NAME` | Application display name. |
| `ZT_APP_VERSION` | Framework/app shell version. |
| `ZT_PUBLIC_PATH` | Absolute path to `public/`. |
| `ZT_STORAGE_PATH` | Absolute path to `storage/`. |
| `ZT_ROUTE_LOGIN` | Login route slug. |
| `ZT_ROUTE_DASHBOARD` | Dashboard route slug. |
| `ZT_BLOCKED_ROUTES` | Route names blocked from dispatch. |
| `ZT_MAX_SEGMENTS` | Maximum route segments. |
| `ZT_MAX_SEGMENT_LENGTH` | Maximum length for a route segment. |

Only truly global framework values should be added here. Prefer module-local constants or configuration for feature-specific values.

## Config Repository

`Config\Config` provides runtime lookup:

```php
use Config\Config;

$enabled = Config::get('ZT_CSRF_ENABLED', false);
```

Lookup order:

1. Explicit values loaded into `Config`.
2. Environment variables.
3. Database settings from `mx_setting`, when available.
4. The provided default.

Database settings are lazy-loaded and cached in memory.

## Path Constants

Use path constants instead of rebuilding paths repeatedly:

```php
ZT_PUBLIC_PATH
ZT_STORAGE_PATH
ZT_CACHE_PATH
ZT_REPORT_PATH
ZT_LOG_ROOT
```

Use `DIRECTORY_SEPARATOR` or `DS` when composing filesystem paths.

