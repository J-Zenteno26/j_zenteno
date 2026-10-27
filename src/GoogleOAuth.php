<?php

declare(strict_types=1);

final class GoogleOAuth
{
    public const AUTHORIZATION_ENDPOINT = 'https://accounts.google.com/o/oauth2/v2/auth';
    public const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';
    public const SCOPE = 'https://www.googleapis.com/auth/business.manage';
    public const REGISTERED_REDIRECT_URI = 'https://www.zentdigital.cl/google/oauth/callback/';
    public const STATE_TTL_SECONDS = 600;

    public static function requireHttps(): void
    {
        $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
        $serverPort = (string) ($_SERVER['SERVER_PORT'] ?? '');

        if ($https !== 'on' && $https !== '1' && $serverPort !== '443') {
            self::fail('Esta operación requiere una conexión HTTPS segura.', 400);
        }
    }

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', '1');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.cookie_path', '/google/oauth/');
        ini_set('session.cookie_lifetime', '0');
        session_name('zent_google_oauth');

        if (!session_start()) {
            self::fail('No fue posible iniciar la sesión administrativa.', 500);
        }
    }

    /**
     * @return array{
     *   client_id:string,
     *   client_secret:string,
     *   redirect_uri:string,
     *   admin_user:string,
     *   admin_password_hash:string,
     *   token_file:string
     * }
     */
    public static function configuration(): array
    {
        $config = [
            'client_id' => self::environment('GOOGLE_CLIENT_ID'),
            'client_secret' => self::environment('GOOGLE_CLIENT_SECRET'),
            'redirect_uri' => self::environment('GOOGLE_REDIRECT_URI'),
            'admin_user' => self::environment('GOOGLE_OAUTH_ADMIN_USER'),
            'admin_password_hash' => self::environment('GOOGLE_OAUTH_ADMIN_PASSWORD_HASH'),
            'token_file' => self::environment('GOOGLE_OAUTH_TOKEN_FILE'),
        ];

        $missing = [];
        foreach ($config as $name => $value) {
            if ($value === '') {
                $missing[] = $name;
            }
        }

        if ($missing !== []) {
            self::fail('La integración OAuth no está completamente configurada en el servidor.', 503);
        }

        if (!hash_equals(self::REGISTERED_REDIRECT_URI, $config['redirect_uri'])) {
            self::fail('La URI de redirección OAuth no coincide con la configuración requerida.', 503);
        }

        if (!password_get_info($config['admin_password_hash'])['algo']) {
            self::fail('La autenticación administrativa OAuth no está configurada correctamente.', 503);
        }

        self::validatePrivateTokenPath($config['token_file']);

        return $config;
    }

    /** @param array{admin_user:string,admin_password_hash:string} $config */
    public static function requireAdministrator(array $config): void
    {
        [$providedUser, $providedPassword] = self::basicCredentials();

        $validUser = $providedUser !== '' && hash_equals($config['admin_user'], $providedUser);
        $validPassword = $providedPassword !== '' && password_verify($providedPassword, $config['admin_password_hash']);

        if (!$validUser || !$validPassword) {
            header('WWW-Authenticate: Basic realm="ZentDigital Google OAuth", charset="UTF-8"');
            self::fail('Se requiere autenticación administrativa para iniciar esta operación.', 401);
        }
    }

    public static function newState(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    public static function storeState(string $state): void
    {
        session_regenerate_id(true);
        $_SESSION['google_oauth_state_hash'] = hash('sha256', $state);
        $_SESSION['google_oauth_state_issued_at'] = time();
    }

    public static function consumeAndValidateState(mixed $providedState): void
    {
        $expectedHash = $_SESSION['google_oauth_state_hash'] ?? null;
        $issuedAt = $_SESSION['google_oauth_state_issued_at'] ?? null;

        unset($_SESSION['google_oauth_state_hash'], $_SESSION['google_oauth_state_issued_at']);

        if (!is_string($providedState) || $providedState === '' || !is_string($expectedHash) || !is_int($issuedAt)) {
            self::fail('La solicitud OAuth no es válida o ya expiró.', 400);
        }

        $age = time() - $issuedAt;
        if ($age < 0 || $age > self::STATE_TTL_SECONDS || !hash_equals($expectedHash, hash('sha256', $providedState))) {
            self::fail('La solicitud OAuth no es válida o ya expiró.', 400);
        }
    }

    /** @param array{client_id:string,redirect_uri:string} $config */
    public static function authorizationUrl(array $config, string $state): string
    {
        $parameters = [
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ];

        return self::AUTHORIZATION_ENDPOINT . '?' . http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @param array{client_id:string,client_secret:string,redirect_uri:string} $config
     * @return array{access_token:string,refresh_token?:string,expires_in:int,scope:string,token_type?:string}
     */
    public static function exchangeCode(array $config, string $code): array
    {
        if (!extension_loaded('curl')) {
            self::fail('El cliente HTTP requerido no está disponible en el servidor.', 500);
        }

        $handle = curl_init(self::TOKEN_ENDPOINT);
        if ($handle === false) {
            self::fail('No fue posible preparar la comunicación con Google.', 500);
        }

        $postFields = http_build_query([
            'code' => $code,
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri' => $config['redirect_uri'],
            'grant_type' => 'authorization_code',
        ], '', '&', PHP_QUERY_RFC3986);

        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_USERAGENT => 'ZentDigital-Google-OAuth/1.0',
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        ]);

        $response = curl_exec($handle);
        $httpStatus = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $curlFailed = $response === false;
        curl_close($handle);

        if ($curlFailed) {
            self::fail('No fue posible comunicarse de forma segura con Google.', 502);
        }

        if ($httpStatus < 200 || $httpStatus >= 300) {
            self::fail('Google rechazó el intercambio de autorización. Inicia nuevamente el proceso.', 502);
        }

        try {
            $tokens = json_decode((string) $response, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            self::fail('Google devolvió una respuesta que no pudo validarse.', 502);
        }

        if (!is_array($tokens)
            || !isset($tokens['access_token'], $tokens['expires_in'], $tokens['scope'])
            || !is_string($tokens['access_token'])
            || $tokens['access_token'] === ''
            || !is_numeric($tokens['expires_in'])
            || (int) $tokens['expires_in'] <= 0
            || !is_string($tokens['scope'])
            || $tokens['scope'] === '') {
            self::fail('La respuesta de autorización de Google está incompleta.', 502);
        }

        $validated = [
            'access_token' => $tokens['access_token'],
            'expires_in' => (int) $tokens['expires_in'],
            'scope' => $tokens['scope'],
        ];

        if (isset($tokens['refresh_token']) && is_string($tokens['refresh_token']) && $tokens['refresh_token'] !== '') {
            $validated['refresh_token'] = $tokens['refresh_token'];
        }

        if (isset($tokens['token_type']) && is_string($tokens['token_type'])) {
            $validated['token_type'] = $tokens['token_type'];
        }

        return $validated;
    }

    public static function persistRefreshToken(string $tokenFile, string $refreshToken, string $scope): void
    {
        self::validatePrivateTokenPath($tokenFile);

        $directory = dirname($tokenFile);
        if (!is_dir($directory) || !is_writable($directory)) {
            self::fail('La ubicación privada para el token no está disponible.', 500);
        }

        $payload = json_encode([
            'refresh_token' => $refreshToken,
            'scope' => $scope,
            'received_at' => gmdate('c'),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $previousUmask = umask(0077);
        $temporaryFile = tempnam($directory, '.zent-google-oauth-');
        umask($previousUmask);

        if ($temporaryFile === false) {
            self::fail('No fue posible preparar el almacenamiento privado del token.', 500);
        }

        $written = file_put_contents($temporaryFile, $payload . PHP_EOL, LOCK_EX);
        if ($written === false) {
            @unlink($temporaryFile);
            self::fail('No fue posible guardar el token en la ubicación privada.', 500);
        }

        @chmod($temporaryFile, 0600);
        if (!@rename($temporaryFile, $tokenFile)) {
            @unlink($temporaryFile);
            self::fail('No fue posible completar el almacenamiento privado del token.', 500);
        }
    }

    public static function renderSuccess(bool $refreshTokenReceived, string $scope): never
    {
        self::securityHeaders();
        http_response_code(200);

        $received = $refreshTokenReceived ? 'Sí' : 'No';
        $safeScope = htmlspecialchars($scope, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeDate = htmlspecialchars(gmdate('Y-m-d H:i:s') . ' UTC', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<title>Autorización Google | ZentDigital</title></head><body>';
        echo '<main><h1>Autorización exitosa</h1><dl>';
        echo '<dt>Refresh token recibido</dt><dd>' . $received . '</dd>';
        echo '<dt>Scope concedido</dt><dd>' . $safeScope . '</dd>';
        echo '<dt>Fecha y hora</dt><dd>' . $safeDate . '</dd>';
        echo '</dl></main></body></html>';
        exit;
    }

    public static function fail(string $publicMessage, int $status): never
    {
        self::securityHeaders();
        http_response_code($status);
        $safeMessage = htmlspecialchars($publicMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<title>OAuth no disponible | ZentDigital</title></head><body><main><h1>No fue posible completar la autorización</h1>';
        echo '<p>' . $safeMessage . '</p></main></body></html>';
        exit;
    }

    private static function environment(string $name): string
    {
        $value = getenv($name);
        return is_string($value) ? trim($value) : '';
    }

    /** @return array{0:string,1:string} */
    private static function basicCredentials(): array
    {
        $user = $_SERVER['PHP_AUTH_USER'] ?? '';
        $password = $_SERVER['PHP_AUTH_PW'] ?? '';

        if (is_string($user) && is_string($password) && ($user !== '' || $password !== '')) {
            return [$user, $password];
        }

        $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (!is_string($authorization) || !preg_match('/^Basic\s+([A-Za-z0-9+\/=]+)$/i', $authorization, $matches)) {
            return ['', ''];
        }

        $decoded = base64_decode($matches[1], true);
        if (!is_string($decoded) || !str_contains($decoded, ':')) {
            return ['', ''];
        }

        [$decodedUser, $decodedPassword] = explode(':', $decoded, 2);
        return [$decodedUser, $decodedPassword];
    }

    private static function validatePrivateTokenPath(string $tokenFile): void
    {
        $isAbsolute = str_starts_with($tokenFile, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $tokenFile) === 1;

        if (!$isAbsolute) {
            self::fail('La ruta privada del token debe ser absoluta.', 503);
        }

        $documentRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
        $tokenDirectory = realpath(dirname($tokenFile));

        if ($tokenDirectory === false) {
            self::fail('El directorio privado configurado para el token no existe.', 503);
        }

        if ($documentRoot !== false) {
            $root = rtrim(strtolower(str_replace('\\', '/', $documentRoot)), '/') . '/';
            $directory = rtrim(strtolower(str_replace('\\', '/', $tokenDirectory)), '/') . '/';

            if (str_starts_with($directory, $root)) {
                self::fail('La ubicación del token debe estar fuera del directorio público.', 503);
            }
        }
    }

    private static function securityHeaders(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; base-uri 'none'; frame-ancestors 'none'; form-action 'none'");
        header('Referrer-Policy: no-referrer');
        header('Content-Type: text/html; charset=UTF-8');
    }
}

