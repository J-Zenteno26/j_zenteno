<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/GoogleOAuth.php';

GoogleOAuth::requireHttps();
GoogleOAuth::startSession();

$config = GoogleOAuth::configuration();
GoogleOAuth::consumeAndValidateState($_GET['state'] ?? null);

$oauthError = $_GET['error'] ?? null;
if (is_string($oauthError) && $oauthError !== '') {
    GoogleOAuth::fail('Google no concedió la autorización solicitada.', 400);
}

$code = $_GET['code'] ?? null;
if (!is_string($code) || $code === '') {
    GoogleOAuth::fail('Google no devolvió un código de autorización válido.', 400);
}

$tokens = GoogleOAuth::exchangeCode($config, $code);
$refreshTokenReceived = isset($tokens['refresh_token']);

if ($refreshTokenReceived) {
    GoogleOAuth::persistRefreshToken($config['token_file'], $tokens['refresh_token'], $tokens['scope']);
}

GoogleOAuth::renderSuccess($refreshTokenReceived, $tokens['scope']);

