# ZanTech Framework Architecture (v2.4.0)

ZanTech is a high-fidelity, modular, URL-driven web application framework built on **PHP 8.2**. It is engineered for secure, enterprise-grade enterprise portals, natively supporting multi-database connection abstractions (MySQL, SQL Server, ODBC, PostgreSQL, SQLite), comprehensive middleware pipelines, Role-Based Access Control (RBAC), and transactional Maker-Checker (Dual Control) approvals.

---

## 1. Directory Blueprint

The repository structure cleanly decouples configuration, framework foundation classes, database layers, and business logic:

```
├── Authentication/       # Session, Captcha, RBAC, and Dual Control approvals
├── Config/               # Config key repositories with env fallback
├── Database/             # Schema, migrations, DB drivers, and Base ORM Model
├── Exceptions/           # Native framework exception mapping & error UI handlers
├── Foundation/           # App loader, web/console kernels, routing, and middlewares
├── Http/                 # HTTP Request, Response, and Base Controller
├── Modules/              # Modular application business features (Dashboard, User, Permission...)
├── Services/             # Cross-cutting validators, adapters, log sanitizers
├── View/                 # HTML UI layouts, shared header/footer, and view engines
├── constants/            # Framework versions and global system preferences
├── helpers/              # Standard global helper libraries
├── public/               # Web Document Root (front-controller entry, assets)
├── storage/              # File cache, session stores, and reports
├── zt / zt.php           # Console CLI commands
```

---

## 2. Request Lifecycle & Routing Execution

Every HTTP request enters the web root and is processed linearly:

1. **Entry**: Web server forwards request URIs through [public/index.php](file:///e:/personal/zanTech/public/index.php) to [Foundation/AppLoader.php](file:///e:/personal/zanTech/Foundation/AppLoader.php).
2. **Bootstrapping**: `AppLoader` configures global exception tracking, executes Composer autoloading, loads configuration arrays, and detects target namespaces (`web`, `api`, or `cronjob`).
3. **Core Initialization**: Passes control to [Foundation/web.php](file:///e:/personal/zanTech/Foundation/web.php) to initialize output buffers, enforce secure session configurations, and bind a unique Request ID.
4. **URL Decomposition**: The router [Foundation/Routing/Router.php](file:///e:/personal/zanTech/Foundation/Routing/Router.php) maps URL paths to modules:
   * **Convention-based**: `/Module/Method/Param1` resolves to class `Modules\Module\Module` invoking action `Method(Param1)`.
   * **Parameterized Routes**: Custom regex mappings defined in `routes.php` translate paths into clean segment chains.
   * **Asset Protection**: Prevents broken static files from invoking heavy MVC cycles.

---

## 3. Middleware Pipeline

ZanTech routes all dispatch actions through a sequential pipeline of middlewares defined in [Foundation/Zantech.php](file:///e:/personal/zanTech/Foundation/Zantech.php):

```
[ Request ] 
    │
    ▼
┌───────────────────────────────┐
│ SecurityHeadersMiddleware     │ <-- Injects CSP, HSTS, frame-blocking, and XSS headers
└───────────────┬───────────────┘
                │
                ▼
┌───────────────────────────────┐
│ AuditMiddleware               │ <-- Records globally mapped timings & activity parameters
└───────────────┬───────────────┘
                │
                ▼
┌───────────────────────────────┐
│ SessionSecurityMiddleware     │ <-- Prevents hijacking via User-Agent & IP fingerprinting
└───────────────┬───────────────┘
                │
                ▼
┌───────────────────────────────┐
│ AuthThrottlingMiddleware      │ <-- Implements login rate-limits and protects against brute-force
└───────────────┬───────────────┘
                │
                ▼
┌───────────────────────────────┐
│ AuthMiddleware & CSRF Checks  │ <-- Gates secure endpoints & validates request tokens
└───────────────┬───────────────┘
                │
                ▼
   [ Controller Action Dispatch ]
```

---

## 4. The MVC & Core Abstractions

### 1. Controllers (`Http\Controller`)
The [Http/Controller.php](file:///e:/personal/zanTech/Http/Controller.php) base controller implements clean lifecycle control:
* **Lazy Rendering**: Views are rendered on demand, minimizing execution overhead during direct API payloads.
* **Unified Response Wrapper**: Offers simple response standards (`responseSuccess()`, `responseError()`, and `responseJson()`).
* **Route Guards**: Evaluates custom privileges using `requirePermission('slug')`.
* **Generic automation (`BaseModuleController`)**: Automatically wires controller instances to their respective models, providing standard grid tables, filtering (Active/Inactive), and automatic listing pagination out of the box.

### 2. Models (`Database\Model`)
Models in [Database/Model.php](file:///e:/personal/zanTech/Database/Model.php) provide robust SQL database interactions:
* **Fluent Query Builder**: Secure parameters validation that enforces parameterized values preventing SQL Injections:
  ```php
  $activeStaff = $this->where('opt_mx_status_id', 1)
                      ->where('txt_domain', 'mx_staff')
                      ->orderBy('id', 'DESC')
                      ->get();
  ```
* **Universal Database Abstraction**: Integrates with [Database\Database](file:///e:/personal/zanTech/Database/Database.php) to automatically balance escaping protocols across SQL Server, MySQL, SQLite, and PostgreSQL.
* **Metadata discovery**: Infers target table/view names dynamically from the child model's namespace prefix.
* **Multi-Connection Database Routing**: Supports routing different models to different databases on the fly. By overriding the `$connection` parameter (e.g. `protected string $connection = 'billing'`) or switching connections dynamically at runtime using `$model->setConnection('billing')`, the model, active record query builders, pagination compilers, and ORM methods instantly redirect all execution routines and SQL dialects to that connection automatically.
* **SQL Profiler & Heavy Query Tracing**: Automatically monitors execution performance on all `select()`, `save()`, and `update()` queries. Any queries taking longer than `ZT_SLOW_QUERY_THRESHOLD` (default 100ms) are logged along with their bound parameters directly into `logs/custom/db/*-slow.log` for optimization.

---

## 5. Transactional Integrity & Governance

### 1. Role-Based Access Control (RBAC)
Authorizations in [Authentication/Perm_Auth.php](file:///e:/personal/zanTech/Authentication/Perm_Auth.php) provide precise access policies:
* **Multi-Group Memberships**: Access privileges are dynamically aggregated across multiple organizational groups assigned to the user.
* **Individual overrides**: Specific capabilities can be directly assigned or revoked from a user's credential ID.
* **Super Admin Bypass**: Accounts with Role ID `1` bypass local verification rules to ensure emergency administrative control.

### 2. Maker-Checker approvals (Dual Control)
To prevent unauthorized transactions or configurations, the dispatcher supports multi-party approvals:
* **Controller Flagging**: Declare gated methods directly on the controller class:
  ```php
  protected array $dualControl = ['save', 'delete'];
  ```
* **Payload Capture**: Gated operations intercept requests before execution, serialize incoming payloads, log them under `mx_dual_activity` with a pending state, and notify the client with a `202 Accepted` status.
* **Execution**: Gated actions remain on hold until authorized by a separate user (Checker) through the Approval panel.

---

## 6. Development CLI (`zt` Tooling)

The ZanTech framework includes a specialized command-line utility for database tasks and rapid module scaffolding:

```bash
# Code Scaffolding
php zt make:module [Name]      # Automatically scaffolds localized MVC structure
php zt make:controller [Name]  # Generates controller actions and corresponding views

# Migration Management
php zt db:init                 # Restores core database schema structure
php zt db:seed                 # Seeds database with system configurations and administrative roles
php zt migrate                 # Executes all pending incremental T-SQL migrations
php zt migrate:status          # Shows applied vs pending migrations in the timeline
```

---

## 7. Frontend Integration & Premium UI Standards

ZanTech coordinates modern responsive interfaces by pairing modular PHP template blocks with AngularJS application stacks:
* **Responsive Styling**: Enforced by **Bootstrap 5.3.3** utilizing curated primary components, fluid spacing layouts, and glassmorphic inputs defined in [zantech-ui.css](file:///e:/personal/zanTech/public/assets/css/zantech-ui.css).
* **Single-Page Mechanics**: Embedded AngularJS applications (v1.8.2) interact with server REST routes to update table structures dynamically without refreshing layouts.
* **Controller-to-Frontend Binding**: Backend attributes are safely serialized into HTML container tags, which the AngularJS app maps upon bootstrapping to maintain seamless dynamic states.

---

## 8. High-Performance Optimization Engine

ZanTech implements active performance optimization strategies to ensure extremely lightweight request loading and execution:

### 1. Lazy-Loaded Single-Query Config Cache
Instead of initiating redundant database queries during bootstrap to load environment parameters or toggles (e.g., rate limit and CSRF statuses), ZanTech employs a **batch cache loading mechanism**:
* **Single Query Execution**: A single database select query retrieves all key-value entries from `mx_setting` during the initial config read.
* **In-Memory Cache**: Populates all configuration settings directly inside a static cache array, rendering subsequent setting lookups instantaneous CPU-memory operations without additional database roundtrips.
* **Non-Blocking Resiliency**: Gracefully fails once and bypasses repetitive lookup processes if the database engine is offline or during test suites.

### 2. Physical I/O Elimination (Production Logging)
File system I/O (writing log rows to disk) is one of the heaviest bottlenecks in PHP execution. ZanTech resolves this by introducing strict **environment guards**:
* **Logging Early-Exit**: When `APP_DEBUG` is false, low-severity log statements (`sysLog()`, `savePlainLog()`, `info()`, `debug()`, and full query-statement logging `queryLog()`) early-exit immediately.
* **Disk Write Reductions**: Decreases physical disk writes from ~10 writes per page load to **exactly 0** for normal requests in production.
* **Anomalous Transparency**: Only high-priority anomalies (like system errors, database exception mapping, email/sms delivery failures, and strict audit trails) bypass guards and write to disk, maintaining ultimate stability and auditability.

### 3. Lightweight Request Bootstrapping (Zero-Allocation Logging)
To ensure the front controller boots instantly on every web request, ZanTech bypasses heavy housekeeping routines:
* **Logging Allocation Bypass**: When `APP_DEBUG` is false, `Zantech::logRequestStart()` completely skips loading session caller users, formatting 7 separate verbose log rows, and executing multiple expensive `json_encode()` calls.
* **Security Guard Preservation**: The critical malicious script input scanner (`scanMaliciousInputs()`) is fully preserved and executed on the redacted request payload, guaranteeing enterprise-grade security at maximum performance.

---

## 9. Bug Fixes, Security Patches & Visual Refinements (v2.4.1)

ZanTech applies target-hardening protocols and layout adjustments with zero changes to existing class APIs or signatures:

### 1. Database Insertion Sanitization (Zero-Crash Binding)
Previously, direct database operations `save()`, `update()`, and `updateFiltered()` constructed PDO bindings directly from array keys. If any array key contained spaces, dashes, or dots, PDO compilation failed.
* **Refinement**: Integrated automatic string-replacement sanitizers that convert illegal parameter characters (`.`, `-`, and spaces) into safe underscores (`_`) on the fly, rendering the raw PDO parameters perfectly compliant.

### 2. Login Security Reinforcement
* **Empty Username Lockdown**: Overhauled `Auth::login()` in [Authentication/Auth.php](file:///e:/personal/zanTech/Authentication/Auth.php) to assert that incoming username payloads are not empty, raising an immediate `AuthException` if attempts are made to authenticate empty strings or blank space characters.

### 3. Log Sanitizer Overhaul (SMS Protection)
* **SMS Payload Redaction**: Upgraded `MXSms::sendTemplateSMS()` in [Services/MXSms.php](file:///e:/personal/zanTech/Services/MXSms.php) to filter its log statements. All phone number recipients are masked using safe format expressions, and all token keys (such as codes, passwords, or secrets) are scrubbed using the core `RouterSecurity::redactSensitive()` engine before being stored.

### 4. Cinematic Split-Panel Visual Hotfixes
* **Contrast Alignment**: Hardened CSS active-state classes in [zantech-ui.css](file:///e:/personal/zanTech/public/assets/css/zantech-ui.css) (such as `.zt-permission-architect .list-group-item.active`) to enforce high-contrast readable color schemes against soft glassmorphic primary overlays.
* **Edge-to-Edge Banners**: Modified margin rules (`margin-left: -3rem`, `margin-right: -3rem`) inside `.zt-dashboard-header` to pull background headers perfectly flush with the screen, eliminating white gaps and layout inconsistencies.

### 5. Dead Code & Legacy Method Cleanup
* **Method Replacement**: Systematically scanned the application to eliminate the deprecated `_permissionDenied()` legacy method. All modules (`DualActivity.php`, `SmsTemplate.php`, `Report.php`, `Miscellaneous.php`, `EmailContent.php`) were safely refactored to use the modern, reflection-based `permissionDenied()` pipeline without arguments.
* **Base Cleanse**: Successfully removed the deprecated wrapper from the abstract [Http/Controller.php](file:///e:/personal/zanTech/Http/Controller.php) template.
