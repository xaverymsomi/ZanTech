# Modules

Modules are the main unit of application behavior in Oryn. Each module groups a controller, model, and views for a feature area.

## Layout

A typical module:

```text
app/Modules/Billing/
  Billing.php
  Billing_Model.php
  Views/
    index.php
    create.php
    edit.php
```

## Create A Module

Use the CLI:

```bash
php oryn make:module Billing
```

This creates:

- `app/Modules/Billing/Billing.php`
- `app/Modules/Billing/Billing_Model.php`
- `app/Modules/Billing/Views/index.php`

For a permission-aware starter module:

```bash
php oryn make:module Billing --example
```

The example scaffold includes a guarded `index()` action, a JSON `status()` action, a small model method for sample rows, and an escaped table view. It is useful when creating the first module in a new application because it shows the controller/model/view contract without requiring a database table.

## Controller

Controllers extend `Http\Controller`.

```php
<?php

namespace Modules\Billing;

use Http\Controller;

class Billing extends Controller
{
    public string $module = 'Billing';

    public function __construct()
    {
        parent::__construct();
        $this->model = new Billing_Model();
    }

    public function index(): void
    {
        $this->view()->title = 'Billing';
        $this->render('index');
    }
}
```

## Model

Models usually extend `Database\Model`.

```php
<?php

namespace Modules\Billing;

use Database\Model;

class Billing_Model extends Model
{
    protected string $table = 'mx_billing';
}
```

## Views

Module views live under `app/Modules/{Module}/Views/`.

Render a view from a controller:

```php
$this->render('index');
```

This loads:

```text
app/Modules/Billing/Views/index.php
```

For XHR requests or `$noLayout = true`, the view renders without the shared layout.

## Naming Rules

Use PascalCase for module folders and controller classes:

```text
app/Modules/EmailContent/EmailContent.php
```

Use the same module name in the namespace:

```php
namespace Modules\EmailContent;
```

Keep model names in the existing convention:

```text
EmailContent_Model.php
```

## Lightweight Module Pattern

Use lightweight endpoints for health and readiness checks. A module action that returns JSON through `responseSuccess()` avoids rendering the shared layout and keeps deploy checks cheap:

```php
public function status()
{
    return $this->responseSuccess(200, 'Billing ready', [
        'module' => 'Billing',
    ]);
}
```

Use permission guards on human-facing screens and privileged API actions:

```php
$perm = Perm_Auth::getPermissions();
if (!$perm->verifyPermission('view_billing')) {
    $this->permissionDenied();
}
```

