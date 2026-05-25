# ZanTech Documentation

This documentation explains how ZanTech is organized, how requests move through the framework, and how to build modules safely.

## Start Here

| Document | Purpose |
|----------|---------|
| [Getting Started](getting-started.md) | Install dependencies, configure the app, initialize the database, and run tests. |
| [Project Structure](project-structure.md) | Understand the `app/`, `src/`, `bootstrap/`, `resources/`, and `public/` directories. |
| [Request Lifecycle](request-lifecycle.md) | Follow a web request from `public/index.php` to controller dispatch. |
| [Routing](routing.md) | Learn convention-based routing, route segments, public routes, static file handling, and custom routes. |
| [Modules](modules.md) | Build feature modules with controllers, models, and views. |
| [Module Tutorial](module-tutorial.md) | Build a small module from scaffold to controller, model, migration, views, and permissions. |
| [Controllers And Responses](controllers-and-responses.md) | Use the base controller, render views, return JSON, validate requests, and enforce permissions. |
| [API And Response Contracts](api-response-contracts.md) | Understand HTML, JSON, success, error, redirect, validation, and request input contracts. |
| [Database And Models](database-and-models.md) | Work with database connections, models, query helpers, migrations, and schema initialization. |
| [Configuration](configuration.md) | Load environment values, use framework constants, and read application settings. |
| [Authentication And Security](authentication-and-security.md) | Understand sessions, permissions, middleware, CSRF, rate limiting, and dual control. |
| [RBAC And Permissions](rbac-and-permissions.md) | Configure permission names, groups, direct overrides, sections, menus, and controller guards. |
| [Views And Frontend](views-and-frontend.md) | Work with shared layouts, module templates, assets, Bootstrap, and AngularJS. |
| [CLI Reference](cli.md) | Use `zt` commands for scaffolding, database tasks, tests, cache clearing, and workers. |
| [Testing](testing.md) | Run PHPUnit and understand the test bootstrap. |
| [Deployment Guide](deployment.md) | Deploy ZanTech safely behind IIS, Apache, or Nginx. |
| [Troubleshooting](troubleshooting.md) | Diagnose common setup, routing, database, permission, and deployment issues. |
| [Contributing Guide](contributing.md) | Follow project conventions for code, modules, security, tests, docs, and commits. |
| [Maintenance Guide](maintenance.md) | Common checks before shipping framework or module changes. |

## Framework Summary

ZanTech is a PHP 8.2 modular MVC framework. It uses Composer PSR-4 autoloading, a public front controller, URL-driven module routing, session authentication, permission-aware controllers, shared PHP views, SQL migrations, and a small CLI named `zt`.

The current layout separates the framework into four main areas:

- `app/Modules/`: application feature modules.
- `src/`: reusable framework code and services.
- `bootstrap/`: environment loading and global constants.
- `resources/views/`: shared layouts and global templates.

The web server should point at `public/`, not the repository root.
