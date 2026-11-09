<?php

declare(strict_types=1);

class AccountController
{
    public function loginForm(): void
    {
        if (current_customer() !== null) {
            redirect('/account');
        }

        header('Cache-Control: no-store, private');
        render('account/login', [
            'pageTitle' => 'Entrar na sua conta',
            'googleEnabled' => $this->googleConfigured(),
            'error' => $this->takeError(),
        ]);
    }

    public function registerForm(): void
    {
        if (current_customer() !== null) {
            redirect('/account');
        }

        header('Cache-Control: no-store, private');
        render('account/register', [
            'pageTitle' => 'Criar conta',
            'error' => $this->takeError(),
        ]);
    }

    public function register(): void
    {
        verify_csrf();

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

        if (
            $name === ''
            || strlen($name) > 100
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($email) > 254
            || !$this->isValidCustomerPassword($password)
            || !hash_equals($password, $passwordConfirmation)
        ) {
            $_SESSION['account_error'] = 'Confira seus dados. A senha deve ter de 8 a 11 caracteres, incluindo maiúscula, minúscula e caractere especial, e coincidir com a confirmação.';
            redirect('/register');
        }

        try {
            $customer = User::createCustomer($name, $email, $password);
        } catch (DomainException $exception) {
            $_SESSION['account_error'] = $exception->getMessage();
            redirect('/register');
        }

        $this->signIn($customer);
        $this->redirectAfterLogin();
    }

    public function login(): void
    {
        verify_csrf();

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        if (strlen($email) > 254) {
            $_SESSION['account_error'] = 'E-mail ou senha inválidos.';
            redirect('/login');
        }
        $rateLimitKey = $this->rateLimitKey($email);

        if ($this->isRateLimited($rateLimitKey)) {
            $_SESSION['account_error'] = 'Muitas tentativas de acesso. Aguarde 15 minutos e tente novamente.';
            redirect('/login');
        }

        $customer = User::authenticateCustomer($email, $password);
        if ($customer === null) {
            $this->recordFailedLogin($rateLimitKey);
            $_SESSION['account_error'] = 'E-mail ou senha inválidos.';
            redirect('/login');
        }

        $this->clearFailedLogins($rateLimitKey);
        $this->signIn($customer);
        $this->redirectAfterLogin();
    }

    public function account(): void
    {
        $customer = require_customer();
        header('Cache-Control: no-store, private');

        render('account/account', [
            'customer' => $customer,
            'orders' => User::customerOrders((int) $customer['id']),
            'googleEnabled' => $this->googleConfigured(),
            'googleLinked' => !empty($customer['google_sub']),
            'error' => $this->takeError(),
            'pageTitle' => 'Minha conta',
        ]);
    }

    public function logout(): void
    {
        verify_csrf();
        unset($_SESSION['customer_id'], $_SESSION['customer_name'], $_SESSION['return_after_login']);
        session_regenerate_id(true);
        redirect('/');
    }

    public function googleStart(): void
    {
        $this->startGoogleOAuth('login', 0);
    }

    public function googleLinkStart(): void
    {
        $customer = require_customer();
        $this->startGoogleOAuth('link', (int) $customer['id']);
    }

    private function startGoogleOAuth(string $intent, int $customerId): void
    {
        if (!$this->googleConfigured()) {
            $_SESSION['account_error'] = 'O acesso com Google ainda não foi configurado nesta instalação.';
            redirect($intent === 'link' ? '/account' : '/login');
        }

        $state = bin2hex(random_bytes(32));
        $verifier = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
        $_SESSION['google_oauth_state'] = $state;
        $_SESSION['google_pkce_verifier'] = $verifier;
        $_SESSION['google_oauth_intent'] = $intent;
        $_SESSION['google_link_customer_id'] = $customerId;

        $google = app_config()['google'];
        $query = http_build_query([
            'client_id' => $google['client_id'],
            'redirect_uri' => $google['redirect_uri'],
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
            'prompt' => 'select_account',
        ]);

        redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    public function googleCallback(): void
    {
        $expectedState = (string) ($_SESSION['google_oauth_state'] ?? '');
        $state = (string) ($_GET['state'] ?? '');
        $verifier = (string) ($_SESSION['google_pkce_verifier'] ?? '');
        $intent = (string) ($_SESSION['google_oauth_intent'] ?? 'login');
        $linkCustomerId = (int) ($_SESSION['google_link_customer_id'] ?? 0);
        unset($_SESSION['google_oauth_state'], $_SESSION['google_pkce_verifier'], $_SESSION['google_oauth_intent'], $_SESSION['google_link_customer_id']);

        if ($expectedState === '' || $state === '' || !hash_equals($expectedState, $state) || $verifier === '') {
            $_SESSION['account_error'] = 'Não foi possível validar o retorno do Google. Tente novamente.';
            redirect(current_customer() !== null ? '/account' : '/login');
        }

        if (isset($_GET['error']) || empty($_GET['code'])) {
            $_SESSION['account_error'] = 'O acesso com Google foi cancelado ou não foi autorizado.';
            redirect($intent === 'link' ? '/account' : '/login');
        }

        try {
            $identity = $this->fetchGoogleIdentity((string) $_GET['code'], $verifier);
            if (
                !filter_var($identity['email'] ?? '', FILTER_VALIDATE_EMAIL)
                || empty($identity['email_verified'])
                || empty($identity['sub'])
                || empty($identity['name'])
            ) {
                throw new RuntimeException('O Google não retornou uma identidade verificada.');
            }

            if ($intent === 'link') {
                $currentCustomer = current_customer();
                if ($currentCustomer === null || (int) $currentCustomer['id'] !== $linkCustomerId) {
                    throw new DomainException('Sua sessão expirou. Entre novamente para vincular o Google.');
                }

                User::linkGoogleToCustomer(
                    $linkCustomerId,
                    strtolower((string) $identity['email']),
                    (string) $identity['sub']
                );
                $_SESSION['account_success'] = 'Sua conta Google foi vinculada com segurança.';
                redirect('/account');
            }

            $customer = User::findCustomerByGoogleSubject((string) $identity['sub']);
            if ($customer === null) {
                $customer = User::createGoogleCustomer(
                    trim((string) $identity['name']),
                    strtolower((string) $identity['email']),
                    (string) $identity['sub']
                );
            }

            $this->signIn($customer);
            $this->redirectAfterLogin();
        } catch (DomainException $exception) {
            $_SESSION['account_error'] = $exception->getMessage();
            redirect($intent === 'link' ? '/account' : '/login');
        } catch (RuntimeException $exception) {
            error_log('Google OAuth authentication failed: ' . $exception->getMessage());
            $_SESSION['account_error'] = 'Não foi possível confirmar sua conta do Google. Tente novamente.';
            redirect($intent === 'link' ? '/account' : '/login');
        }
    }

    private function fetchGoogleIdentity(string $code, string $verifier): array
    {
        $google = app_config()['google'];
        $tokenResponse = $this->googleRequest(
            'https://oauth2.googleapis.com/token',
            'POST',
            ['Content-Type: application/x-www-form-urlencoded'],
            http_build_query([
                'code' => $code,
                'client_id' => $google['client_id'],
                'client_secret' => $google['client_secret'],
                'redirect_uri' => $google['redirect_uri'],
                'grant_type' => 'authorization_code',
                'code_verifier' => $verifier,
            ])
        );

        if (empty($tokenResponse['access_token']) || ($tokenResponse['token_type'] ?? '') !== 'Bearer') {
            throw new RuntimeException('A troca do código OAuth foi recusada.');
        }

        return $this->googleRequest(
            'https://openidconnect.googleapis.com/v1/userinfo',
            'GET',
            ['Authorization: Bearer ' . $tokenResponse['access_token']]
        );
    }

    private function googleRequest(string $url, string $method, array $headers, string $body = ''): array
    {
        $options = [
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'ignore_errors' => true,
                'timeout' => 10,
                'follow_location' => 0,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
            ],
        ];

        if ($method === 'POST') {
            $options['http']['content'] = $body;
        }

        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            throw new RuntimeException('Não foi possível estabelecer uma conexão HTTPS verificada com o Google.');
        }

        $statusLine = $http_response_header[0] ?? '';
        if (!preg_match('/\s2\d\d\s/', $statusLine)) {
            throw new RuntimeException('O Google recusou a solicitação OAuth.');
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            throw new RuntimeException('O Google retornou uma resposta inválida.');
        }

        return $data;
    }

    private function googleConfigured(): bool
    {
        $google = app_config()['google'];
        $redirect = parse_url($google['redirect_uri']) ?: [];
        $secureRedirect = ($redirect['scheme'] ?? '') === 'https'
            || (
                ($redirect['scheme'] ?? '') === 'http'
                && in_array($redirect['host'] ?? '', ['localhost', '127.0.0.1'], true)
            );

        return $google['client_id'] !== ''
            && $google['client_secret'] !== ''
            && $secureRedirect
            && extension_loaded('openssl')
            && in_array('ssl', stream_get_transports(), true);
    }

    private function signIn(array $customer): void
    {
        session_regenerate_id(true);
        unset($_SESSION['admin_logged_in'], $_SESSION['admin_user']);
        $_SESSION['customer_id'] = (int) $customer['id'];
        $_SESSION['customer_name'] = (string) $customer['name'];
    }

    private function redirectAfterLogin(): void
    {
        $destination = $_SESSION['return_after_login'] ?? '/account';
        unset($_SESSION['return_after_login']);
        redirect($destination === '/checkout' ? '/checkout' : '/account');
    }

    private function takeError(): string
    {
        $error = (string) ($_SESSION['account_error'] ?? '');
        unset($_SESSION['account_error']);
        return $error;
    }

    private function rateLimitKey(string $email): string
    {
        $address = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        return hash('sha256', strtolower($email) . '|' . $address);
    }

    private function isRateLimited(string $key): bool
    {
        Database::boot();
        $statement = Database::pdo()->prepare(
            'SELECT attempts FROM login_attempts
             WHERE attempt_key = :attempt_key AND first_attempt_at >= UTC_TIMESTAMP() - INTERVAL 15 MINUTE'
        );
        $statement->execute(['attempt_key' => $key]);
        return (int) $statement->fetchColumn() >= 5;
    }

    private function recordFailedLogin(string $key): void
    {
        Database::boot();
        $statement = Database::pdo()->prepare(
            'INSERT INTO login_attempts (attempt_key, attempts, first_attempt_at)
             VALUES (:attempt_key, 1, UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE
                attempts = IF(first_attempt_at < UTC_TIMESTAMP() - INTERVAL 15 MINUTE, 1, attempts + 1),
                first_attempt_at = IF(first_attempt_at < UTC_TIMESTAMP() - INTERVAL 15 MINUTE, UTC_TIMESTAMP(), first_attempt_at)'
        );
        $statement->execute(['attempt_key' => $key]);
    }

    private function clearFailedLogins(string $key): void
    {
        Database::boot();
        $statement = Database::pdo()->prepare('DELETE FROM login_attempts WHERE attempt_key = :attempt_key');
        $statement->execute(['attempt_key' => $key]);
    }

    private function isValidCustomerPassword(string $password): bool
    {
        return preg_match('/\A(?=.*[A-Z])(?=.*[a-z])(?=.*[^A-Za-z0-9])[\x21-\x7E]{8,11}\z/', $password) === 1;
    }
}
