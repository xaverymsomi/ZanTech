# Module Tutorial

This tutorial builds a small `Billing` module. It follows ZanTech conventions and can be adapted for real feature modules.

## 1. Generate The Module

```bash
php zt make:module Billing
```

Generated files:

```text
app/Modules/Billing/Billing.php
app/Modules/Billing/Billing_Model.php
app/Modules/Billing/Views/index.php
```

## 2. Add A Migration

Create a migration file:

```text
src/Database/migrations/20260601_create_mx_billing_table.sql
```

Example SQL Server migration:

```sql
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='mx_billing' AND xtype='U')
BEGIN
    CREATE TABLE mx_billing (
        id BIGINT IDENTITY(1,1) PRIMARY KEY,
        txt_invoice_no NVARCHAR(80) NOT NULL,
        dec_amount DECIMAL(18,2) NOT NULL,
        opt_mx_status_id INT NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT GETDATE()
    );
END
```

Run tracked migrations:

```bash
php zt migrate
```

Or run only the file:

```bash
php zt db:migrate src/Database/migrations/20260601_create_mx_billing_table.sql
```

## 3. Configure The Model

Edit:

```text
app/Modules/Billing/Billing_Model.php
```

```php
<?php

namespace Modules\Billing;

use Database\Model;

class Billing_Model extends Model
{
    protected string $table = 'mx_billing';

    public function activeInvoices(): array
    {
        return $this->where('opt_mx_status_id', 1)
            ->orderBy('id', 'DESC')
            ->get();
    }
}
```

Keep database work in the model so controllers stay small.

## 4. Build The Controller

Edit:

```text
app/Modules/Billing/Billing.php
```

```php
<?php

namespace Modules\Billing;

use Http\Controller;
use Http\Response;

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
        $this->requirePermission('view_billing');

        $this->view()->title = 'Billing';
        $this->view()->invoices = $this->model->activeInvoices();
        $this->render('index');
    }

    public function save(): Response
    {
        $this->requirePermission('add_billing');

        $data = $this->validate([
            'txt_invoice_no' => 'required|max:80',
            'dec_amount' => 'required|numeric',
        ]);

        $id = $this->model->save($this->model->getTable(), $data);

        return $this->responseSuccess(201, 'Invoice saved', [
            'id' => $id,
        ]);
    }
}
```

## 5. Create The View

Edit:

```text
app/Modules/Billing/Views/index.php
```

```php
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="zt-card">
                <div class="zt-card__header">
                    <h5><?= $this->e($this->title ?? 'Billing') ?></h5>
                </div>
                <div class="zt-card__body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($this->invoices ?? []) as $invoice): ?>
                                <tr>
                                    <td><?= $this->e($invoice['txt_invoice_no'] ?? '') ?></td>
                                    <td><?= $this->e((string)($invoice['dec_amount'] ?? '0.00')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
```

Always escape user-controlled values with `$this->e()`.

## 6. Add Permissions

Create permission records for:

```text
view_billing
add_billing
edit_billing
delete_billing
```

Assign them through group permissions or direct user permission overrides.

If this module should appear in the sidebar, make sure the menu and section records align with the RBAC/menu structure.

## 7. Add Tests

Add a focused test under `tests/`, for example:

```text
tests/BillingModuleTest.php
```

Prefer testing controller-independent behavior first:

- Model table configuration.
- Validation rules.
- Response helper shape.
- Route helper behavior.

Run:

```bash
php zt test
```

## 8. Verify In Browser

Visit:

```text
/Billing/index
```

If you see a permission error, verify the current user has `view_billing`.

If you see a missing view error, confirm:

```text
app/Modules/Billing/Views/index.php
```

