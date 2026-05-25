# Contributing Guide

This guide documents the conventions to follow when changing ZanTech.

## Branches And Commits

Use short, descriptive commit messages:

```text
Add billing module
Fix route normalization
Document deployment workflow
```

Keep unrelated changes in separate commits.

## Coding Style

Follow the style already used in the touched file.

General conventions:

- PHP 8.2 compatible code.
- One class per file.
- Namespace must match Composer PSR-4 mapping.
- Keep controllers thin.
- Put data access in models.
- Use service classes for cross-cutting behavior.
- Escape user-controlled output in views.

## Directory Conventions

Use:

```text
app/Modules/       Feature modules.
src/               Framework and reusable services.
bootstrap/         Boot configuration and constants.
resources/views/   Shared templates.
public/assets/     Browser assets.
tests/             PHPUnit tests.
docs/              Documentation.
```

Do not add new framework roots without updating Composer and documentation.

## Module Conventions

Module folders and controllers use PascalCase:

```text
app/Modules/Billing/Billing.php
```

Namespace:

```php
namespace Modules\Billing;
```

Model:

```text
Billing_Model.php
```

Permissions:

```text
view_billing
add_billing
edit_billing
delete_billing
```

## Security Rules

- Do not commit `.env`.
- Do not commit real credentials, API keys, SMS tokens, or database passwords.
- Use `requirePermission()` for privileged controller actions.
- Use request validation for input.
- Escape output in views.
- Keep `public/` as the only web-exposed directory.
- Keep `APP_DEBUG=0` in production.

## Tests

Run:

```bash
php zt test
```

Also run:

```bash
composer validate --no-check-publish
```

Composer warnings about existing dependency constraints may remain, but new validation errors should be fixed before merging.

## Documentation

Update docs when changing:

- Project layout.
- Routing behavior.
- CLI commands.
- Environment keys.
- Response contracts.
- Security behavior.
- Database or migration workflow.

For frontend dependency changes, update:

- `resources/views/header.php`
- `VERSIONS.md`
- `cdn-lock.json`

## Do Not Commit

The following should stay untracked or ignored:

- `vendor/`
- `.env`
- `.env.*` except `.env.example`
- `.phpunit.cache/`
- `logs/`
- Runtime files under `storage/`, except `storage/.gitkeep`

