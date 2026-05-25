<?php



namespace Modules\Login;

use Authentication\Auth;
use Authentication\CaptchaLib;
use Authentication\Session;
use Http\Controller;
use Logging\Log;
use Modules\Error\Error;
use Throwable;

final class Login extends Controller
{
    public function __construct()
    {
        parent::__construct();

        /** @var Login_Model $model */
        $this->model = new Login_Model();

        Auth::isLogged();
    }

    public function index()
    {
        $error = filter_input(INPUT_GET, 'error');
        $message = null;

        if ($error) {
            $message = match ($error) {
                'INACTIVITY_TIMEOUT' => 'Your session has expired due to inactivity. Please log in again.',
                'SESSION_IP_MISMATCH' => 'Security Alert: Your IP address has changed. Session terminated for your protection.',
                'SESSION_UA_MISMATCH' => 'Security Alert: Your browser identity has changed. Session terminated.',
                default => 'Your session has been terminated for security reasons.'
            };
        }

        $this->view()->title = 'Login';
        $this->view()->error_message = $message;
        $this->render('index');
    }

    public function get_captcha()
    {
        Session::init();

        $cap = new CaptchaLib();
        $cap->generateCapture();
    }

    public function login()
    {
        Log::sysLog('Initiating Login Sequence');

        Session::init();

        $isSpa = $this->isSpaRequest();

        try {
            [$email, $pass, $captcha] = $this->readLoginPayload();
            $returnUrl = (string)(filter_input(INPUT_GET, 'return_url') ?: '');

            // Your model does redirects for legacy flow
            $this->model->initiateLogin($email, $pass, $returnUrl, $captcha);

            // If model didn't redirect (SPA use-case), respond JSON
            if ($isSpa) {
                return $this->responseJson(['status' => true, 'message' => 'Login successful'], 200);
            }
        } catch (Throwable $e) {
            \Services\AuditTrail::log('LOGIN_FAILED', "Email: {$email}", ['error' => $e->getMessage()]);
            Log::exception($e, 'LOGIN_FAILURE', ['action' => 'login']);

            $publicMessage = $this->isDebug()
                ? ('Login failed: ' . $e->getMessage())
                : 'Login failed. Please try again.';

            if ($isSpa) {
                return $this->responseJson(['status' => false, 'message' => $publicMessage], 400);
            }

            (new Error(
                'Login failed',
                $publicMessage,
                null,
                'bi-sign-do-not-enter-fill'
            ))->index();
        }
    }

    public function recover()
    {
        Log::sysLog('Initiating Recovering Sequence');

        Session::init();
        $isSpa = $this->isSpaRequest();

        try {
            $email = (string)(filter_input(INPUT_POST, 'email') ?: '');
            \Services\AuditTrail::log('PASSWORD_RECOVERY_REQUESTED', "Email: {$email}");
            $this->model->recover($email);

            if ($isSpa) {
                return $this->responseJson(['status' => true, 'message' => 'Recovery initiated'], 200);
            }
        } catch (Throwable $e) {
            \Services\AuditTrail::log('PASSWORD_RECOVERY_FAILED', "Email: {$email}", ['error' => $e->getMessage()]);
            Log::exception($e, 'LOGIN_RECOVER_FAILURE', ['action' => 'recover']);

            $publicMessage = $this->isDebug()
                ? ('Recover failed: ' . $e->getMessage())
                : 'Recovery failed. Please try again.';

            if ($isSpa) {
                return $this->responseJson(['status' => false, 'message' => $publicMessage], 400);
            }

            (new Error('Recover failed', $publicMessage, null, 'bi-exclamation-triangle-fill'))->index();
        }
    }

    public function reset()
    {
        $token = filter_input(INPUT_GET, 'token') ?: filter_input(INPUT_POST, 'token');
        $email = filter_input(INPUT_GET, 'email') ?: filter_input(INPUT_POST, 'email');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = filter_input(INPUT_POST, 'password');
            $password_match = filter_input(INPUT_POST, 'password_match');

            $this->model->reset((string)$token, (string)$email, (string)$password, (string)$password_match);
            \Services\AuditTrail::log('PASSWORD_RESET_SUCCESS', "Email: {$email}");
        } else {
            // Render the reset password view
            $this->view()->title = 'Reset Password';
            $this->view()->token = $token;
            $this->view()->email = $email;
            $this->render('reset');
        }
    }

    /* ============================================================
       Helpers
    ============================================================ */

    private function isDebug(): bool
    {
        $v = $_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG');
        if ($v === false || $v === null) return false;
        return in_array(strtolower(trim((string)$v)), ['1', 'true', 'yes', 'on'], true);
    }

    private function isSpaRequest(): bool
    {
        $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
        $xhr    = (string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        $spa    = (string)($_GET['spa'] ?? '');

        return $xhr === 'XMLHttpRequest'
            || stripos($accept, 'application/json') !== false
            || $spa === '1';
    }

    /**
     * @return array{0:string,1:string,2:string}
     */
    private function readLoginPayload(): array
    {
        $contentType = (string)($_SERVER['CONTENT_TYPE'] ?? '');
        $jsonBody = [];

        if (stripos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw ?: '', true);
            $jsonBody = is_array($decoded) ? $decoded : [];
        }

        $email   = (string)($jsonBody['email'] ?? (filter_input(INPUT_POST, 'email') ?: ''));
        $pass    = (string)($jsonBody['password'] ?? (filter_input(INPUT_POST, 'password') ?: ''));
        $captcha = (string)($jsonBody['captcha'] ?? (filter_input(INPUT_POST, 'captcha') ?: ''));

        return [$email, $pass, $captcha];
    }
}
