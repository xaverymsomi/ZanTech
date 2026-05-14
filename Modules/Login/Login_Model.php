<?php

declare(strict_types=1);

namespace Modules\Login;

use Authentication\Auth;
use Authentication\Session;
use Database\Database;
use DateTime;
use Exceptions\ZantechException;
use Library\CaptchaLib;
use Library\Model;
use Logging\Log;
use Services\Hash;
use Throwable;

final class Login_Model extends Model
{
    private string $title = "Dashboard";

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
            Session::set('returned', 1993);
            Log::sysLog('LOGIN_BLOCKED_CAPTCHA');
            header("Location: " . URL);
            exit;
        }

        $hashed = Hash::create(HASH_ALGO, (string)$password, PASS_SALT);

        $result = $this->sysLogin('mx_login_credential', (string)$email, $hashed);
        Log::sysLog([
            'event' => 'LOGIN_DB_RESULT',
            'ok' => !empty($result),
        ]);

        if (empty($result)) {
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
            'id'             => $result[1]['id'],
            'credential_id'  => $result[1]['id'],
            'txt_username'   => $result[1]['txt_username'] ?? $email,
            'txt_name'       => $result[1]['txt_name'] ?? '',
            'txt_domain'     => $result[1]['txt_domain'] ?? '',
            'opt_mx_group_id'=> $result[0]['opt_mx_group_id'] ?? 0,
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
          AND txt_password = :password
          AND opt_mx_status_id = 1
    ";

        $result = $db->select($sql, [
            ':email'    => $email,
            ':password' => $password,
        ]);

        if (!$result) return [];

        $user = $result[0];

        // ✅ whitelist domain tables
        $allowedDomains = ['mx_user', 'mx_agent', 'mx_staff'];
        if (!in_array($user['txt_domain'], $allowedDomains, true)) {
            throw new ZantechException("Invalid domain table {$user['txt_domain']}", "Account configuration error", 500);
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
            Session::set('returned', 6061);
            header("Location: " . URL);
            exit;
        }

        $user = $data[0];

        // NOTE: Your reset token logic is legacy; later we should replace it with secure random tokens stored in DB with expiry.
        $link = URL . '/login/reset?udid=' . md5((string)(1290 * 3 + (int)($user['int_token'] ?? 0)));

        $otp  = $this->generateRandomString(8);
        $hash = Hash::create(HASH_ALGO, $otp, PASS_SALT);

        $db->prepare("UPDATE mx_login_credential SET txt_password = :pwd WHERE user_id = :uid")
            ->execute([':pwd' => $hash, ':uid' => $user['id']]);

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

    public function reset(string $usr, string $password, string $password_match): void
    {
        Session::init();

        $db = new Database();

        $sql = "
            SELECT *
            FROM mx_user
            WHERE CONVERT(VARCHAR(32),
            HashBytes('MD5', CONVERT (VARCHAR(32), int_token + 1290 * 3)),2) = :hash
        ";

        $data = $db->select($sql, [':hash' => $usr]);

        if (empty($data) || (int)($data[0]['opt_mx_status_id'] ?? 0) !== 1) {
            Session::set('returned', 6061);
            header("Location: " . URL);
            exit;
        }

        if ($password !== $password_match) {
            Session::set('returned', 6063);
            header("Location: " . URL);
            exit;
        }

        $hash  = Hash::create(HASH_ALGO, $password, PASS_SALT);
        $token = $this->generateRandomNo();

        $db->prepare("UPDATE mx_user SET dat_date_last_reset = :dt, int_token = :tk WHERE id = :id")
            ->execute([
                ':dt' => date('Y-m-d H:i:s'),
                ':tk' => $token,
                ':id' => $data[0]['id']
            ]);

        $db->prepare("UPDATE mx_login_credential SET txt_password = :pwd WHERE user_id = :uid")
            ->execute([
                ':pwd' => $hash,
                ':uid' => $data[0]['id']
            ]);

        Session::set('returned', 6000);
        header("Location: " . URL);
        exit;
    }
}
