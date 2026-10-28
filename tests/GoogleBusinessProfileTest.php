<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/GoogleBusinessProfile.php';

final class GoogleBusinessProfileTestRunner
{
    private int $passed = 0;
    private int $failed = 0;
    /** @var array<int,string> */
    private array $temporaryDirectories = [];

    public function run(): int
    {
        $this->test('renueva el access token y normaliza las reseñas', fn () => $this->successfulRefresh());
        $this->test('maneja errores del token endpoint', fn () => $this->tokenEndpointError());
        $this->test('descubre account_id y location_id sin hardcodearlos', fn () => $this->discoversIdentifiers());
        $this->test('pagina y lista reseñas', fn () => $this->paginatesReviews());
        $this->test('devuelve caché vigente sin consultar Google', fn () => $this->freshCache());
        $this->test('actualiza caché expirado', fn () => $this->expiredCache());
        $this->test('usa fallback cuando Google falla', fn () => $this->fallback());
        $this->test('rechaza JSON inválido', fn () => $this->invalidJson());
        $this->test('rechaza ausencia de refresh token', fn () => $this->missingRefreshToken());
        $this->test('ignora caché con estructura inválida', fn () => $this->invalidCache());
        $this->test('permite seleccionar una ubicación por ID explícito', fn () => $this->explicitLocationId());

        $this->cleanup();
        echo sprintf("\n%d pruebas correctas; %d fallidas.\n", $this->passed, $this->failed);
        return $this->failed === 0 ? 0 : 1;
    }

    private function successfulRefresh(): void
    {
        $directory = $this->directory();
        $requests = [];
        $service = $this->service($directory, function (string $method, string $url, array $headers, ?string $body) use (&$requests): array {
            $requests[] = [$method, $url, $headers, $body];
            return $this->successResponse($url);
        });

        $result = $service->getReviews();
        self::assertSame('api', $result['source']);
        self::assertSame('account-123', $result['account_id']);
        self::assertSame('location-456', $result['location_id']);
        self::assertSame(4.8, $result['average_rating']);
        self::assertSame(17, $result['total_reviews']);
        self::assertSame('Ada', $result['reviews'][0]['author']);
        self::assertSame(5, $result['reviews'][0]['rating']);
        self::assertSame('Muchas gracias', $result['reviews'][0]['owner_reply']);
        self::assertTrue(is_file($directory . DIRECTORY_SEPARATOR . 'cache.json'));

        $tokenRequest = $requests[0];
        self::assertSame('POST', $tokenRequest[0]);
        self::assertTrue(str_contains((string) $tokenRequest[3], 'grant_type=refresh_token'));
        self::assertTrue(str_contains((string) $tokenRequest[3], 'refresh_token=test-refresh-token'));
    }

    private function tokenEndpointError(): void
    {
        $service = $this->service($this->directory(), static fn (): array => [
            'status' => 400,
            'body' => '{"error":"invalid_grant"}',
        ]);
        self::assertThrows(fn () => $service->getReviews(), GoogleBusinessProfileException::class);
    }

    private function discoversIdentifiers(): void
    {
        $service = $this->service($this->directory(), function (string $method, string $url): array {
            if (str_contains($url, '/accounts?')) {
                return ['status' => 200, 'body' => json_encode([
                    'accounts' => [['name' => 'accounts/other'], ['name' => 'accounts/correct']],
                ], JSON_THROW_ON_ERROR)];
            }
            if (str_contains($url, '/accounts/other/locations')) {
                return ['status' => 200, 'body' => '{"locations":[{"name":"locations/wrong","title":"Otra empresa"}]}'];
            }
            return $this->successResponse($url);
        });
        $result = $service->getReviews();
        self::assertSame('correct', $result['account_id']);
        self::assertSame('location-456', $result['location_id']);
    }

    private function paginatesReviews(): void
    {
        $reviewCalls = 0;
        $service = $this->service($this->directory(), function (string $method, string $url) use (&$reviewCalls): array {
            if (str_contains($url, '/reviews?')) {
                $reviewCalls++;
                if ($reviewCalls === 1) {
                    return ['status' => 200, 'body' => json_encode([
                        'averageRating' => 4.8,
                        'totalReviewCount' => 2,
                        'reviews' => [$this->review('review-1')],
                        'nextPageToken' => 'next-review-page',
                    ], JSON_THROW_ON_ERROR)];
                }
                self::assertTrue(str_contains($url, 'pageToken=next-review-page'));
                return ['status' => 200, 'body' => json_encode([
                    'reviews' => [$this->review('review-2')],
                ], JSON_THROW_ON_ERROR)];
            }
            return $this->successResponse($url);
        });
        $result = $service->getReviews();
        self::assertSame(2, count($result['reviews']));
        self::assertSame(2, $reviewCalls);
    }

    private function freshCache(): void
    {
        $directory = $this->directory();
        $this->writeCache($directory, time() - 60);
        $service = $this->service($directory, static function (): array {
            throw new RuntimeException('Google should not be called for a fresh cache.');
        });
        $result = $service->getReviews();
        self::assertSame('cache', $result['source']);
        self::assertSame(false, $result['stale']);
    }

    private function expiredCache(): void
    {
        $directory = $this->directory();
        $this->writeCache($directory, time() - 21601);
        $calls = 0;
        $service = $this->service($directory, function (string $method, string $url) use (&$calls): array {
            $calls++;
            return $this->successResponse($url);
        });
        $result = $service->getReviews();
        self::assertSame('api', $result['source']);
        self::assertTrue($calls > 0);
    }

    private function fallback(): void
    {
        $directory = $this->directory();
        $this->writeCache($directory, time() - 30000);
        $service = $this->service($directory, static fn (): array => ['status' => 503, 'body' => '{}']);
        $result = $service->getReviews();
        self::assertSame('fallback', $result['source']);
        self::assertSame(true, $result['stale']);
    }

    private function invalidJson(): void
    {
        $service = $this->service($this->directory(), static fn (): array => ['status' => 200, 'body' => '{']);
        self::assertThrows(fn () => $service->getReviews(), GoogleBusinessProfileException::class);
    }

    private function missingRefreshToken(): void
    {
        $directory = $this->directory();
        $service = $this->service($directory, static fn (): array => ['status' => 200, 'body' => '{}'], false);
        self::assertThrows(fn () => $service->getReviews(), GoogleBusinessProfileException::class);
    }

    private function invalidCache(): void
    {
        $directory = $this->directory();
        file_put_contents($directory . DIRECTORY_SEPARATOR . 'cache.json', '{"updated_at":"now"}');
        $calls = 0;
        $service = $this->service($directory, function (string $method, string $url) use (&$calls): array {
            $calls++;
            return $this->successResponse($url);
        });
        $result = $service->getReviews();
        self::assertSame('api', $result['source']);
        self::assertTrue($calls > 0);
    }

    private function explicitLocationId(): void
    {
        $directory = $this->directory();
        $tokenFile = $directory . DIRECTORY_SEPARATOR . 'token.json';
        file_put_contents($tokenFile, '{"refresh_token":"test-refresh-token"}');
        $service = new GoogleBusinessProfile([
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect_uri' => 'https://www.zentdigital.cl/google/oauth/callback/',
            'token_file' => $tokenFile,
            'cache_file' => $directory . DIRECTORY_SEPARATOR . 'cache.json',
            'account_id' => 'accounts/account-123',
            'location_id' => 'locations/location-456',
            'location_name' => 'Un nombre que puede cambiar',
            'cache_ttl' => 21600,
        ], fn (string $method, string $url): array => $this->successResponse($url));

        $result = $service->getReviews();
        self::assertSame('account-123', $result['account_id']);
        self::assertSame('location-456', $result['location_id']);
    }

    private function service(string $directory, callable $request, bool $createToken = true): GoogleBusinessProfile
    {
        $tokenFile = $directory . DIRECTORY_SEPARATOR . 'token.json';
        if ($createToken && !is_file($tokenFile)) {
            file_put_contents($tokenFile, '{"refresh_token":"test-refresh-token"}');
        }
        return new GoogleBusinessProfile([
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect_uri' => 'https://www.zentdigital.cl/google/oauth/callback/',
            'token_file' => $tokenFile,
            'cache_file' => $directory . DIRECTORY_SEPARATOR . 'cache.json',
            'location_name' => 'ZentDigital',
            'cache_ttl' => 21600,
        ], $request);
    }

    /** @return array{status:int,body:string} */
    private function successResponse(string $url): array
    {
        if ($url === GoogleBusinessProfile::TOKEN_ENDPOINT) {
            return ['status' => 200, 'body' => '{"access_token":"temporary-access-token","expires_in":3600,"token_type":"Bearer"}'];
        }
        if (str_contains($url, '/accounts?')) {
            return ['status' => 200, 'body' => '{"accounts":[{"name":"accounts/account-123"}]}'];
        }
        if (str_contains($url, '/locations?')) {
            return ['status' => 200, 'body' => '{"locations":[{"name":"locations/location-456","title":"ZentDigital"}]}'];
        }
        if (str_contains($url, '/reviews?')) {
            return ['status' => 200, 'body' => json_encode([
                'averageRating' => 4.8,
                'totalReviewCount' => 17,
                'reviews' => [$this->review('review-1')],
            ], JSON_THROW_ON_ERROR)];
        }
        throw new RuntimeException('Unexpected test URL.');
    }

    /** @return array<string,mixed> */
    private function review(string $id): array
    {
        return [
            'reviewId' => $id,
            'reviewer' => ['displayName' => 'Ada', 'profilePhotoUrl' => 'https://example.com/ada.jpg'],
            'starRating' => 'FIVE',
            'comment' => 'Excelente trabajo',
            'createTime' => '2026-01-01T10:00:00Z',
            'updateTime' => '2026-01-02T10:00:00Z',
            'reviewReply' => ['comment' => 'Muchas gracias', 'updateTime' => '2026-01-03T10:00:00Z'],
        ];
    }

    private function writeCache(string $directory, int $updatedAt): void
    {
        file_put_contents($directory . DIRECTORY_SEPARATOR . 'cache.json', json_encode([
            'updated_at' => gmdate('c', $updatedAt),
            'account_id' => 'cached-account',
            'location_id' => 'cached-location',
            'location_name' => 'ZentDigital',
            'average_rating' => 4.5,
            'total_reviews' => 10,
            'source' => 'api',
            'stale' => false,
            'reviews' => [[
                'id' => 'cached-review',
                'author' => 'Ada',
                'author_photo' => null,
                'rating' => 5,
                'comment' => 'Excelente',
                'created_at' => '2026-01-01T10:00:00+00:00',
                'updated_at' => '2026-01-02T10:00:00+00:00',
                'owner_reply' => null,
                'owner_reply_updated_at' => null,
            ]],
        ], JSON_THROW_ON_ERROR));
    }

    private function directory(): string
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'zent-gbp-' . bin2hex(random_bytes(6));
        if (!mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('Could not create test directory.');
        }
        $this->temporaryDirectories[] = $directory;
        return $directory;
    }

    private function test(string $name, callable $test): void
    {
        try {
            $test();
            $this->passed++;
            echo "[OK] {$name}\n";
        } catch (Throwable $exception) {
            $this->failed++;
            echo "[FAIL] {$name}: {$exception->getMessage()}\n";
        }
    }

    private function cleanup(): void
    {
        foreach ($this->temporaryDirectories as $directory) {
            foreach (glob($directory . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($directory);
        }
    }

    private static function assertSame(mixed $expected, mixed $actual): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException('Assertion failed.');
        }
    }

    private static function assertTrue(bool $condition): void
    {
        if (!$condition) {
            throw new RuntimeException('Assertion failed.');
        }
    }

    private static function assertThrows(callable $callback, string $class): void
    {
        try {
            $callback();
        } catch (Throwable $exception) {
            if ($exception instanceof $class) {
                return;
            }
            throw $exception;
        }
        throw new RuntimeException('Expected exception was not thrown.');
    }
}

exit((new GoogleBusinessProfileTestRunner())->run());
