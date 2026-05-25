# Troubleshooting

This guide lists common Oryn setup and runtime problems.

## Missing Composer Autoload

Symptom:

```text
Oryn Foundation Error: Run "composer install" to generate autoload.php
```

Fix:

```bash
composer install
```

If `composer.json` autoload mappings changed:

```bash
composer dump-autoload
```

## Blank Page

Likely causes:

- `APP_DEBUG=0` hides the detailed error.
- PHP fatal error before the error layout can render.
- Web server points to the wrong document root.
- Missing dependencies.

Fix:

1. Check PHP/web server logs.
2. Temporarily set `APP_DEBUG=1` in local or staging.
3. Confirm the document root is `public/`.
4. Run:

   ```bash
   composer install
   php oryn test
   ```

## Database Connection Failure

Likely causes:

- Wrong `DB_TYPE`, `DB_HOST`, `DB_NAME`, `DB_USER`, or `DB_PASS`.
- Missing PDO driver.
- SQL Server or database service is not reachable.
- Firewall or network rule blocks access.

Fix:

1. Confirm `.env` database values.
2. Confirm the matching PHP PDO extension is installed.
3. Test credentials with a database client.
4. Run:

   ```bash
   php oryn migrate:status
   ```

## Route Not Found

Likely causes:

- URL module segment does not match the module folder/class.
- Method segment does not map to an existing controller method.
- Module is blocked or not allowlisted.
- Custom route in `routes.php` maps unexpectedly.

Fix:

1. Confirm the module path:

   ```text
   app/Modules/Menu/Menu.php
   ```

2. Confirm the namespace and class:

   ```php
   namespace Modules\Menu;

   class Menu
   ```

3. Confirm the method exists.
4. Check `ZT_ALLOWED_MODULES` if it is set.
5. Check `routes.php`.

## View Missing

Symptom:

```text
View not found: Module/view.php
```

Fix:

Make sure the template exists under:

```text
app/Modules/{Module}/Views/{view}.php
```

For a controller call:

```php
$this->render('index');
```

the file should be:

```text
app/Modules/{Module}/Views/index.php
```

## Permission Denied

Likely causes:

- The current user is not assigned the required permission.
- Group permissions are not configured.
- Menu/section RBAC rows are inconsistent.
- The controller calls `requirePermission()` with the wrong slug.

Fix:

1. Confirm the permission key used by the controller.
2. Confirm user/group permission assignment.
3. Confirm menu and section names line up where sidebar visibility depends on RBAC.
4. Test with a super admin account if available.

## Migrations Do Not Run

Likely causes:

- Database connection failure.
- Migration already recorded in `zt_migrations`.
- SQL file path is wrong.
- SQL dialect does not match the selected database driver.

Fix:

```bash
php oryn migrate:status
php oryn migrate
```

For a single file:

```bash
php oryn db:migrate src/Database/migrations/example.sql
```

Use `GO` separators on their own line for SQL Server batches.

## Composer Validate Warnings

`composer validate --no-check-publish` may warn about unbound dependency versions or missing license metadata. These warnings are not always blockers, but new errors should be fixed before release.

Run:

```bash
composer validate --no-check-publish
```

## Tests Cannot Find PHPUnit

Likely cause:

- Composer dependencies were not installed.

Fix:

```bash
composer install
php oryn test
```

On Windows, direct PHPUnit execution may be:

```bash
vendor\bin\phpunit.bat
```

## Static Assets Return 404

Likely causes:

- Web server document root is not `public/`.
- Asset path includes the wrong `APP_DIR`.
- File does not exist under `public/assets/`.

Fix:

1. Confirm the web root points at `public/`.
2. Confirm `APP_DIR` in `.env`.
3. Confirm the asset file exists.

## Logs Are Too Noisy

Likely cause:

- `APP_DEBUG=1`.

Fix:

Set production/staging environments to:

```dotenv
APP_DEBUG=0
```

Keep detailed debug logging for local development only.

