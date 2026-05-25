# Project Structure

Oryn separates application code, framework code, boot files, shared templates, and public assets.

```text
app/
  Modules/                 Application modules.
bootstrap/
  config.php               Environment loading and `zt_env()`.
  sys_pref.php             Global framework constants.
public/
  index.php                Web front controller.
  assets/                  CSS, JavaScript, images, fonts.
resources/
  views/                   Shared layout files and global templates.
src/
  Authentication/          Auth, sessions, permissions, captcha, dual control.
  Config/                  Runtime configuration repository.
  Database/                DB access, models, migrations, schema.
  Exceptions/              Framework exception types and handler.
  Foundation/              App loader, kernels, routing, middleware, console tools.
  helpers/                 Global helper functions and error handler.
  Http/                    Request, response, controller base classes.
  Logging/                 Logging implementation.
  Notification/            Notification helpers.
  Services/                Cross-cutting services.
  Validation/              Request/input validation.
  View/                    View renderer and UI helpers.
storage/                   Cache, generated files, reports, runtime storage.
tests/                     PHPUnit tests.
oryn                       Preferred CLI entry.
zt / zt.php                Legacy CLI aliases.
```

## App Code

Application features live in `app/Modules/`. A module usually contains:

```text
app/Modules/User/
  User.php
  User_Model.php
  Views/
```

The namespace remains `Modules\User`, even though the files live under `app/Modules/User/`.

## Framework Code

Framework services live in `src/`. Namespaces map directly to directories through Composer:

```json
"Foundation\\": "src/Foundation/",
"Database\\": "src/Database/",
"Modules\\": "app/Modules/"
```

Run `composer dump-autoload` after changing namespace mappings.

## Public Files

Only `public/` should be exposed to the web. It contains:

- `index.php`: front controller.
- `assets/css`: stylesheets.
- `assets/js`: bundled and modular frontend scripts.
- `assets/images`: image assets.
- `assets/fonts`: font assets.

## Boot Files

`bootstrap/config.php` loads `.env` and defines `zt_env()`.

`bootstrap/sys_pref.php` defines global constants such as app version, path constants, route names, security limits, and mode flags.

