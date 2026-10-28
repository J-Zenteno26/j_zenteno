<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/GoogleOAuth.php';
require_once __DIR__ . '/../../../src/GoogleBusinessProfile.php';

GoogleOAuth::requireHttps();
$oauthConfig = GoogleOAuth::configuration();
GoogleOAuth::requireAdministrator($oauthConfig);

try {
    $diagnostics = GoogleBusinessProfile::fromEnvironment()->diagnostics();
} catch (Throwable) {
    $diagnostics = [
        'account_found' => false,
        'location_found' => false,
        'location_name' => null,
        'total_reviews' => null,
        'average_rating' => null,
        'updated_at' => null,
        'source' => 'unavailable',
    ];
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; base-uri 'none'; frame-ancestors 'none'; form-action 'none'");
header('Referrer-Policy: no-referrer');
header('Content-Type: text/html; charset=UTF-8');

$yesNo = static fn (bool $value): string => $value ? 'Sí' : 'No';
$display = static fn (mixed $value): string => htmlspecialchars(
    $value === null || $value === '' ? 'No disponible' : (string) $value,
    ENT_QUOTES | ENT_SUBSTITUTE,
    'UTF-8'
);
$sourceLabels = ['api' => 'API', 'cache' => 'Caché', 'fallback' => 'Caché (fallback)', 'unavailable' => 'No disponible'];
$source = $sourceLabels[$diagnostics['source']] ?? 'No disponible';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Diagnóstico Google Business Profile | ZentDigital</title>
</head>
<body>
<main>
  <h1>Diagnóstico Google Business Profile</h1>
  <dl>
    <dt>Cuenta encontrada</dt><dd><?= $yesNo($diagnostics['account_found']) ?></dd>
    <dt>Ubicación encontrada</dt><dd><?= $yesNo($diagnostics['location_found']) ?></dd>
    <dt>Nombre de la ubicación</dt><dd><?= $display($diagnostics['location_name']) ?></dd>
    <dt>Cantidad de reseñas</dt><dd><?= $display($diagnostics['total_reviews']) ?></dd>
    <dt>Rating promedio</dt><dd><?= $display($diagnostics['average_rating']) ?></dd>
    <dt>Última actualización</dt><dd><?= $display($diagnostics['updated_at']) ?></dd>
    <dt>Origen</dt><dd><?= $display($source) ?></dd>
  </dl>
</main>
</body>
</html>
