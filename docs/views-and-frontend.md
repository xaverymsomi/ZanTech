# Views And Frontend

ZanTech renders PHP templates and uses Bootstrap, AngularJS, and bundled JavaScript assets for interactive screens.

## Shared Layout

Global layout files live in:

```text
resources/views/
  header.php
  body.php
  footer.php
```

`View\ViewRenderer` loads these files for full-page rendering.

## Module Views

Module views live in:

```text
app/Modules/{Module}/Views/
```

Render from a controller:

```php
$this->render('index');
```

Render without layout:

```php
$this->render('index', true);
```

XHR requests are also rendered without the full layout.

## Escaping

Use the view escaping helper for user-provided values:

```php
<?= $this->e($name) ?>
```

This calls `htmlspecialchars()` with UTF-8 handling.

## UI Helpers

`View\Component` and `View\DataView` provide reusable view utilities and table rendering helpers used by existing modules.

## Frontend Assets

Public assets live in:

```text
public/assets/
  css/
  js/
  images/
  fonts/
```

Core frontend files include:

- `public/assets/css/zantech-ui.css`
- `public/assets/js/zantech.bundle.js`
- `public/assets/js/core/app.js`
- `public/assets/js/core/api.client.js`
- `public/assets/js/controllers/`

## CDN Versions

CDN dependencies are declared in `resources/views/header.php` and documented in:

- `VERSIONS.md`
- `cdn-lock.json`

Update all three together when changing frontend library versions.

