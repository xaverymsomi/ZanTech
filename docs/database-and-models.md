# Database And Models

ZanTech database code lives under `src/Database/`.

## Main Classes

| Class | Purpose |
|-------|---------|
| `Database\Database` | Low-level database connection and driver behavior. |
| `Database\DB` | Static connection helper. |
| `Database\Model` | Base model used by module models. |
| `Database\QueryBuilder` | Fluent query behavior. |
| `Database\Migrations\MigrationRunner` | Tracks and runs migration files. |
| `Database\Schema\DatabaseInitializer` | Runs schema and seed SQL files. |

## Model Convention

Module models extend `Database\Model`:

```php
namespace Modules\User;

use Database\Model;

class User_Model extends Model
{
    protected string $table = 'mx_user';
}
```

## Query Examples

Fluent reads:

```php
$users = $this->where('opt_mx_status_id', 1)
    ->orderBy('id', 'DESC')
    ->get();
```

Single record lookup and record writes use helpers provided by `Database\Model` and its traits. Prefer model helpers instead of composing SQL strings in controllers.

## Connections

Models can target alternate connections where supported:

```php
protected string $connection = 'billing';
```

At runtime:

```php
$model->setConnection('billing');
```

Keep connection names consistent with configuration and environment values.

## Schema Initialization

Core schema files live in:

```text
src/Database/Schema/
```

Run:

```bash
php zt db:init
php zt db:seed
```

## Migrations

Incremental SQL migrations live in:

```text
src/Database/migrations/
```

Run a single migration file:

```bash
php zt db:migrate src/Database/migrations/20260212_create_mx_menu_table.sql
```

Run tracked pending migrations:

```bash
php zt migrate
```

Check status:

```bash
php zt migrate:status
```

Tracked migrations are recorded in `zt_migrations`.

## SQL Batches

`MigrationRunner` supports SQL Server `GO` batch separators. Use uppercase `GO` on its own line for clarity.

