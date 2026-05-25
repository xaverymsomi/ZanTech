# Testing

Oryn uses PHPUnit.

## Run Tests

```bash
php oryn test
```

Or directly:

```bash
vendor/bin/phpunit
```

On Windows:

```bash
vendor\bin\phpunit.bat
```

## Configuration

PHPUnit configuration lives in `phpunit.xml`.

The test bootstrap is `tests/bootstrap.php`. It loads Composer autoload and defines minimal constants needed for unit tests without booting the full web runtime.

## What The Tests Cover

The test suite includes coverage for:

- App loader path normalization and entry resolution.
- Routing security.
- HTTP request and response helpers.
- Controller response helpers.
- View rendering.
- Validation.
- Configuration.
- Logging.
- Middleware pipeline behavior.
- Migration runner behavior.
- Module generator behavior.
- Authentication support behavior.

## Writing Tests

Place tests under `tests/` and name them with the `Test.php` suffix.

Prefer isolated tests that do not require a live database unless the behavior being tested is database-specific. For database abstractions, use small fake connections or test doubles where practical.

## Generated Test Artifacts

Ignored runtime artifacts include:

- `.phpunit.cache/`
- `logs/`
- `vendor/`

Do not commit generated cache, logs, or installed dependencies.

