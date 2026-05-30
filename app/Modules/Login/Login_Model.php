<?php



namespace Modules\Login;

use Authentication\Auth;
use Authentication\CaptchaLib;
use Authentication\Session;
use Database\Database;
use Foundation\Middleware\AuthThrottlingMiddleware;
use Http\Request;
use DateTime;
use Exceptions\OrynException;
use Database\Model;
use Logging\Log;
use Services\Hash;
use Services\MXMailGun;
use Services\MXSms;
use Throwable;

final class Login_Model extends Model
{
    protected string $title = "Dashboard";

    public function getTitle(): string
    {
        return $this->title;
    }

    /* ============================================================
     * LOGIN
     * ============================================================ */

    public function initiateLogin(?string $email, ?string $password, ?string $return_url, ?string $captcha_string): void {
        Log::sysLog([
            'event' => 'LOGIN_ATTEMPT',
            'email' => (string)$email,
            'return_url' => (string)$return_url,
        ]);
        $redirect = URL . '/' . ltrim((string)$return_url, '/');
        $redirectToDashboard = URL . '/dashboard';


        $captcha = new CaptchaLib();
        $captcha_response = $captcha->testCapture($captcha_string);

        Log::sysLog([
            'event' => 'LOGIN_CAPTCHA_RESULT',
            'status' => $captcha_response['status'] ?? null,
            'raw' => $captcha_response,
        ]);

        if (($captcha_response['status'] ?? 500) !== 200) {
            AuthThrottlingMiddleware::recordFailure((new Request())->ip(), (string)$email);
            Session::set('returned', 1993);
            Log::sysLog('LOGIN_BLOCKED_CAPTCHA');
            header("Location: " . URL);
            exit;
        }

        $result = $this->sysLogin('mx_login_credential', (string)$email, (string)$password);
        Log::sysLog([
            'event' => 'LOGIN_DB_RESULT',
            'ok' => !empty($result),
        ]);

        if (empty($result)) {
            AuthThrottlingMiddleware::recordFailure((new Request())->ip(), (string)$email);
            Session::set('returned', 10);
            Log::sysLog('LOGIN_FAILED_CREDENTIALS');
            header("Location: " . (URL . '/' . $return_url));
            exit;
        }

        [$groupRow, $userRow, $theme] = $result;

        // 4) PASSWORD POLICY (if you later enable it)
        $validity = $this->isValidPassword($userRow['dat_date_last_reset'] ?? null);

        // ✅ NEW: set canonical auth session used by Auth::isLogged()
        Auth::login([
            'id'                => $result[1]['id'],
            'credential_id'     => $result[1]['id'],
            'txt_username'      => $result[1]['txt_username'] ?? $email,
            'txt_name'          => $result[1]['txt_name'] ?? '',
            'txt_domain'        => $result[1]['txt_domain'] ?? '',
            'opt_mx_group_id'   => $result[0]['opt_mx_group_id'] ?? 0,
            'bit_is_superadmin' => $result[1]['bit_is_superadmin'] ?? 0,
        ]);

        // 5) SESSION KEYS
        Session::set('LAST_ACTIVITY', time());

        // cookies (theme/colors)
        $this->setUiCookies(
            (string)($theme['txt_name'] ?? 'bcx'),
            (string)($theme['txt_primary_colour'] ?? '000000'),
            (string)($theme['txt_secondary_colour'] ?? 'ff0000')
        );

        Session::set('rp_signed_in', true);
        Session::set('username', (string)($userRow['txt_name'] ?? ''));
        Session::set('user_id', (string)($userRow['user_id'] ?? ''));
        Session::set('id', (string)($userRow['id'] ?? ''));
        Session::set('row_value', (string)($userRow['txt_row_value'] ?? ''));
        Session::set('domain', (string)($userRow['txt_domain'] ?? ''));
        Session::set('role', $groupRow['opt_mx_group_id'] ?? null);
        Session::set('validity', $validity);
        Session::set('login_type', 'user');

        Log::sysLog('Login Successful');
        AuthThrottlingMiddleware::clearThrottle((new Request())->ip(), (string)$email);

        header("Location: {$redirectToDashboard}");
        exit;
    }

    /**
     * Allow only relative safe paths (prevents open redirects).
     * Examples allowed:
     *  - ""  -> ""
     *  - "login" -> "login"
     *  - "dashboard/index" -> "dashboard/index"
     *
     * Blocks:
     *  - "http://evil.com"
     *  - "//evil.com"
     *  - "\evil"
     */
    private function sanitizeReturnUrl(string $returnUrl): string
    {
        $u = trim($returnUrl);

        if ($u === '') return '';

        // reject scheme or scheme-relative
        if (preg_match('#^[a-zA-Z][a-zA-Z0-9+\-.]*://#', $u)) return '';
        if (str_starts_with($u, '//')) return '';
        if (str_contains($u, '\\')) return '';

        // keep only path-ish safe chars
        $u = ltrim($u, '/');
        if ($u === '') return '';

        if (!preg_match('#^[a-zA-Z0-9/_\-]+$#', $u)) {
            return '';
        }

        return $u;
    }

    private function setUiCookies(string $theme, string $primary, string $secondary): void
    {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443);

        $opts = [
            'expires'  => time() + 31556926, // ~1 year
            'path'     => '/',
            'secure'   => $isHttps,
            'httponly' => false, // UI JS might read these. If not needed, change to true.
            'samesite' => 'Lax',
        ];

        setcookie('theme', $theme, $opts);
        setcookie('primary', $primary, $opts);
        setcookie('secondary', $secondary, $opts);

        // If you don’t use this, remove it. If you do, prefer JSON over serialize.
        setcookie('scheme', json_encode([]), $opts);
    }

    /* ============================================================
     * LOGIN QUERY
     * ============================================================ */

    /**
     * @return array{0:array,1:array,2:array}|array{}
     */
    public function sysLogin(string $table, string $email, string $password): array
    {
        $db = new Database();

        $tableQ = method_exists($db, 'quoteTable') ? $db->quoteTable($table) : $table;

        $sql = "
        SELECT *
        FROM {$tableQ}
        WHERE txt_username = :email
          AND opt_mx_status_id = 1
    ";

        $result = $db->select($sql, [
            ':email'    => $email,
        ]);

        if (!$result) return [];

        $user = $result[0];

        // 🛡️ Secure Password Verification (Handles modern and legacy hashes)
        if (!Hash::check($password, $user['txt_password'])) {
            return [];
        }

        // 🔄 Rehash-on-Login (Auto-upgrades legacy hashes to modern ones)
        if (Hash::needsRehash($user['txt_password'])) {
            $newHash = Hash::make($password);
            $db->update($table, ['txt_password' => $newHash], $user['id'], 'id');
            Log::sysLog('Password rehashed to modern algorithm', ['user_id' => $user['id']]);
        }

        // ✅ whitelist domain tables
        $allowedDomains = ['mx_user', 'mx_agent', 'mx_staff'];
        if (!in_array($user['txt_domain'], $allowedDomains, true)) {
            throw new OrynException("Invalid domain table {$user['txt_domain']}", "Account configuration error", 500);
        }

        $domainQ = method_exists($db, 'quoteTable') ? $db->quoteTable($user['txt_domain']) : $user['txt_domain'];

        $profile = $db->select("SELECT * FROM {$domainQ} WHERE id = :id", [
            ':id' => $user['user_id'],
        ]);

        if (!$profile) return [];

        foreach ($profile[0] as $k => $v) {
            if ($k !== 'id') $user[$k] = $v;
        }

        $group = $db->select(
            "SELECT * FROM mx_login_credential_group WHERE opt_mx_login_credential_id = :id",
            [':id' => $user['id']]
        );
        if (!$group) return [];

        $theme = [
            'txt_name'             => 'bcx',
            'txt_primary_colour'   => '000000',
            'txt_secondary_colour' => 'ff0000',
        ];

        return [$group[0], $user, $theme, []];
    }

    /* ============================================================
     * PASSWORD POLICY
     * ============================================================ */

    private function isValidPassword(?string $last_reset): bool
    {
        // keep your current behavior
        return true;

        // If you enable it later:
        // if (!$last_reset) return false;
        // $diff = (new DateTime())->diff(new DateTime($last_reset))->days;
        // return $diff < (int)$this->getCouncilPasswordPolicy();
    }

    private function getCouncilPasswordPolicy(): int
    {
        $db = new Database();
        $res = $db->select(
            "SELECT txt_value FROM mx_rule_configuration WHERE int_mx_rule_id = :rule",
            [':rule' => 1]
        );

        return (int)($res[0]['txt_value'] ?? 30);
    }

    /* ============================================================
     * UPDATE LOGIN STATE
     * ============================================================ */

    private function updateUserState(string $table, int $state, int|string $user_id): void
    {
        $db = new Database();
        $tableQ = $db->quoteTable($table);

        $stmt = $db->prepare("UPDATE {$tableQ} SET int_active = :state WHERE id = :id");
        $stmt->execute([':state' => $state, ':id' => $user_id]);
    }

    /* ============================================================
     * PASSWORD RESET (REQUEST)
     * ============================================================ */

    public function recover(string $email): void
    {
        Session::init();

        $email = trim($email);
        $db = new Database();

        $data = $db->select(
            "SELECT * FROM mx_user WHERE email = :email",
            [':email' => $email]
        );

        if (empty($data) || (int)($data[0]['opt_mx_status_id'] ?? 0) !== 1) {
            AuthThrottlingMiddleware::recordFailure((new Request())->ip(), (string)$email);
            Session::set('returned', 6061);
            header("Location: " . URL);
            exit;
        }

        $user = $data[0];

        // Generate a secure, randomized OTP and Token
        $otp   = $this->generateRandomString(8);
        $token = random_int(100000000, 999999999); // Secure 9-digit token for integer column
        $hash  = Hash::make($otp);

        // Store the secure token in the user's row
        $db->prepare("UPDATE mx_user SET int_token = :tk WHERE id = :id")
            ->execute([':tk' => $token, ':id' => $user['id']]);

        $db->prepare("UPDATE mx_login_credential SET txt_password = :pwd WHERE user_id = :uid")
            ->execute([':pwd' => $hash, ':uid' => $user['id']]);

        // Construct the secure link
        $link = URL . '/login/reset?token=' . $token . '&email=' . urlencode($email);

        (new MXSms())->sendTemplateSMS(
            2,
            (string)$user['txt_mobile'],
            $user['id'],
            null,
            null,
            ['_link', '_name', '_password'],
            [$link, (string)$user['txt_name'], $otp],
            1
        );

        (new MXMailGun())->sendEmail(
            2,
            $email,
            null,
            ['_link', '_name', '_password'],
            [$link, (string)$user['txt_name'], $otp]
        );

        Session::set('returned', 6000);
        header("Location: " . URL);
        exit;
    }

    /* ============================================================
     * PASSWORD RESET (FINAL)
     * ============================================================ */

    public function reset(string $token, string $email, string $password, string $password_match): void
    {
        Session::init();

        $db = new Database();

        // Secure lookup: MUST match both Email and the exact randomized Token
        $sql = "SELECT * FROM mx_user WHERE email = :email AND int_token = :token";
        $data = $db->select($sql, [':email' => $email, ':token' => $token]);

        if (empty($data) || (int)($data[0]['opt_mx_status_id'] ?? 0) !== 1) {
            AuthThrottlingMiddleware::recordFailure((new Request())->ip(), (string)$email);
            Session::set('returned', 6061);
            header("Location: " . URL);
            exit;
        }

        if ($password !== $password_match) {
            AuthThrottlingMiddleware::recordFailure((new Request())->ip(), (string)$email);
            Session::set('returned', 6063);
            header("Location: " . URL);
            exit;
        }

        $hash  = Hash::make($password);
        $db->prepare("UPDATE mx_user SET dat_date_last_reset = :dt, int_token = NULL WHERE id = :id")
            ->execute([
                ':dt' => date('Y-m-d H:i:s'),
                ':id' => $data[0]['id']
            ]);

        $db->prepare("UPDATE mx_login_credential SET txt_password = :pwd WHERE user_id = :uid")
            ->execute([
                ':pwd' => $hash,
                ':uid' => $data[0]['id']
            ]);

        AuthThrottlingMiddleware::clearThrottle((new Request())->ip(), (string)$email);

        Session::set('returned', 6000);
        header("Location: " . URL);
        exit;
    }
}
