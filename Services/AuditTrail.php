<?php

namespace Services;

use Authentication\Auth;
use Database\DB;
use Http\Request;
use Foundation\Routing\RouterSecurity;
use Logging\Log;
use Throwable;

/**
 * AuditTrail Service
 * 
 * Captures "who, what, when, for what, through whom" for every sensitive action.
 */
final class AuditTrail
{
    /** @var array Track actions already logged in this request */
    private static array $loggedActions = [];

    /**
     * Log a system event
     *
     * @param string $action    Categorical action (e.g. USER_UPDATE, LOGIN_FAILED)
     * @param string|null $ref  Human readable reference (e.g. "User ID: 442")
     * @param array|null $data  Optional payload to be serialized
     */
    public static function log(string $action, ?string $ref = null, ?array $data = null): void
    {
        $actionKey = strtoupper($action);
        
        // Prevent duplicate identical logs in the same request (e.g. Manual + Middleware)
        if (isset(self::$loggedActions[$actionKey])) {
            return;
        }

        try {
            self::$loggedActions[$actionKey] = true;
            $request = Request::capture();
            $db = DB::connection();

            $user = Auth::user();
            $userId = (int)($user['id'] ?? 0);
            $username = (string)($user['txt_username'] ?? 'GUEST');

            // 1. Redact sensitive data if payload provided
            $payload = null;
            if ($data !== null) {
                $payload = json_encode(RouterSecurity::redactSensitive($data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } elseif (!empty($_POST)) {
                // Auto-capture POST if no data provided and it's a POST request
                $payload = json_encode(RouterSecurity::redactSensitive($_POST), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            // 2. Resolve Module/Method context
            $urlSegments = array_filter(explode('/', trim($request->uri(), '/')));
            $module = $urlSegments[0] ?? 'ROOT';
            $method = $urlSegments[1] ?? 'index';

            // 3. Capture Environmental Fingerprint
            $ip = $request->ip();
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
            $requestId = $_SERVER['ZT_REQUEST_ID'] ?? 'REQ-' . bin2hex(random_bytes(8));

            // 4. Persistence
            $sql = "INSERT INTO mx_audit_trail 
                    (opt_mx_login_credential_id, txt_username, txt_action, txt_module, txt_method, 
                     txt_reference, txt_payload, txt_ip_address, txt_user_agent, txt_request_id, dat_created_at)
                    VALUES 
                    (:uid, :uname, :action, :mod, :met, :ref, :pay, :ip, :ua, :rid, GETDATE())";

            $db->prepare($sql)->execute([
                ':uid'    => ($userId > 0) ? $userId : null,
                ':uname'  => $username,
                ':action' => strtoupper($action),
                ':mod'    => strtoupper($module),
                ':met'    => strtoupper($method),
                ':ref'    => $ref,
                ':pay'    => $payload,
                ':ip'     => $ip,
                ':ua'     => $ua,
                ':rid'    => $requestId
            ]);

            // Also mirror to syslog for developer visibility
            Log::info("AUDIT_TRAIL: [{$action}] by [{$username}] - Ref: {$ref}");

        } catch (Throwable $e) {
            // Never let audit logging crash the main application, but log the failure
            Log::sysErr('AUDIT_TRAIL_FAILURE: ' . $e->getMessage());
        }
    }
}
