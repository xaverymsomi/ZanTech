# Routing

Oryn primarily uses convention-based URL routing with optional custom route definitions.

## Convention

The default route shape is:

```text
/{Module}/{method}/{param1}/{param2}
```

Examples:

| URL | Controller |
|-----|------------|
| `/Menu/index` | `Modules\Menu\Menu::index()` |
| `/User/create` | `Modules\User\User::create()` |
| `/Permission/get_all_menus` | `Modules\Permission\Permission::getAllMenus()` |

Snake-case method segments are converted to camelCase where supported by the router/dispatcher.

## Module Resolution

Modules live under `app/Modules/{ModuleName}` and use the `Modules\` namespace.

The dispatcher expects a controller class that matches the module name:

```text
app/Modules/Menu/Menu.php
```

```php
namespace Modules\Menu;

class Menu extends \Http\Controller
{
}
```

## Public Controllers

The routing layer distinguishes public controllers from authenticated ones. Public routes such as login can be reached before a user is authenticated. Protected routes pass through the authentication middleware.

Check `src/Foundation/Routing/RouteContext.php`, `src/Foundation/Routing/Router.php`, and `src/Foundation/Middleware/AuthMiddleware.php` when changing public-route behavior.

## Custom Routes

Custom routes are defined in `routes.php`.

Use custom routes when a clean URL should map to a conventional module/action segment chain. Keep route definitions narrow and avoid patterns that can shadow module routes unexpectedly.

## Static File Requests

`Foundation\Oryn` detects static file requests under `public/`. Existing static files should be served by the web server. Broken static paths return `404` instead of falling through to module dispatch.

## Route Security

Route security is enforced by:

- URI normalization in `AppLoaderFunctions.php`.
- Route segment length and count constants in `bootstrap/sys_pref.php`.
- Static file detection in `RouterSecurity`.
- Middleware checks during dispatch.

