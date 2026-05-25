# Authentication And Security

Oryn combines session authentication, permission checks, middleware, request normalization, and optional maker-checker approvals.

## Authentication Classes

| Class | Purpose |
|-------|---------|
| `Authentication\Auth` | Login and authenticated-user helpers. |
| `Authentication\Session` | Secure session initialization and access. |
| `Authentication\LoginCheck` | Route authentication checks. |
| `Authentication\Perm_Auth` | Permission lookup and verification. |
| `Authentication\DualControl` | Maker-checker approval support. |
| `Authentication\CaptchaLib` | Captcha verification helpers. |

## Session Initialization

`src/Foundation/web.php` initializes sessions for non-CLI requests through `Authentication\Session::init()`.

Session hardening is also supported by `SessionSecurityMiddleware`, which can validate request fingerprints such as user agent and IP details.

## Middleware Pipeline

The dispatcher runs middleware before controller actions:

```text
SecurityHeadersMiddleware
AuditMiddleware
SessionSecurityMiddleware
AuthThrottlingMiddleware
RateLimitMiddleware       optional
CsrfMiddleware            optional
AuthMiddleware
```

`RateLimitMiddleware` and `CsrfMiddleware` are enabled through runtime configuration.

## Permission Checks

Controllers should guard privileged actions:

```php
$this->requirePermission('edit_menu');
```

If verification fails, the base controller throws `ForbiddenException`.

## Dual Control

Dual control supports maker-checker workflows. A controller can declare actions that require approval:

```php
protected array $dualControl = ['save', 'delete'];
```

When configured, the operation payload is captured and stored as a pending activity. A separate checker can approve the action through the approval flow.

## Route And Input Protections

Security helpers include:

- URI normalization before namespace detection.
- Forbidden boot-probe checks for direct kernel file access.
- Static file request detection.
- Route segment count and length limits.
- Sensitive payload redaction in logs.
- Request validation via `Http\Request` and `Validation\Validator`.

## Production Logging

Debug-level logging should be disabled in production through `APP_DEBUG=0`. Error, audit, and anomaly logging can still write important events.

