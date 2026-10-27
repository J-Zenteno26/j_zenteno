<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/GoogleOAuth.php';

GoogleOAuth::requireHttps();
GoogleOAuth::startSession();

$config = GoogleOAuth::configuration();
GoogleOAuth::requireAdministrator($config);

$state = GoogleOAuth::newState();
GoogleOAuth::storeState($state);

$authorizationUrl = GoogleOAuth::authorizationUrl($config, $state);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Referrer-Policy: no-referrer');
header('Location: ' . $authorizationUrl, true, 302);
exit;

