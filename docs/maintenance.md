# Maintenance Guide

Use this checklist before shipping framework or module changes.

## Before Changing Structure

- Update `composer.json` PSR-4 mappings if namespaces move.
- Run `composer dump-autoload`.
- Update boot paths in `public/index.php`, `src/Foundation/AppLoader.php`, and `zt`.
- Update tests that load files directly.
- Update documentation paths.

## Before Changing Routes

- Check `src/Foundation/Routing/Router.php`.
- Check `src/Foundation/Routing/RouteContext.php`.
- Check `src/Foundation/Routing/RouterSecurity.php`.
- Verify public routes still work.
- Verify static file requests do not enter controller dispatch.

## Before Changing Modules

- Keep namespace and folder names aligned.
- Keep controllers thin and move data access into models.
- Use `requirePermission()` for privileged actions.
- Use response helpers for API endpoints.
- Escape user-controlled data in views.

## Before Changing Database Code

- Prefer model/query helpers over raw SQL in controllers.
- Keep migration files idempotent where possible.
- Use `GO` batch separators only on their own line.
- Test migration behavior with `php zt migrate:status`.

## Before Changing Frontend Dependencies

Update all of these together:

- `resources/views/header.php`
- `VERSIONS.md`
- `cdn-lock.json`

## Standard Verification

Run:

```bash
composer validate --no-check-publish
php zt test
```

Composer may report existing dependency warnings. Treat new validation failures as blockers.

