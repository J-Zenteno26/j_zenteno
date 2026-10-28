<?php

declare(strict_types=1);

final class GoogleBusinessProfileException extends RuntimeException
{
}

final class GoogleBusinessProfile
{
    public const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';
    public const ACCOUNTS_ENDPOINT = 'https://mybusinessaccountmanagement.googleapis.com/v1/accounts';
    public const BUSINESS_INFORMATION_ENDPOINT = 'https://mybusinessbusinessinformation.googleapis.com/v1';
    public const REVIEWS_ENDPOINT = 'https://mybusiness.googleapis.com/v4';
    public const DEFAULT_CACHE_TTL_SECONDS = 21600;

    /** @var array<string, mixed> */
    private array $config;
    private Closure $request;
    private Closure $clock;

    /**
     * @param array<string, mixed> $config
     * @param callable(string,string,array<int,string>,?string):array{status:int,body:string}|null $request
     * @param callable():int|null $clock
     */
    public function __construct(array $config, ?callable $request = null, ?callable $clock = null)
    {
        $this->config = $this->validateConfiguration($config);
        $this->request = $request === null
            ? Closure::fromCallable([$this, 'curlRequest'])
            : Closure::fromCallable($request);
        $this->clock = $clock === null
            ? static fn (): int => time()
            : Closure::fromCallable($clock);
    }

    public static function fromEnvironment(): self
    {
        $tokenFile = self::environment('GOOGLE_OAUTH_TOKEN_FILE');
        $cacheFile = self::environment('GOOGLE_BUSINESS_CACHE_FILE');

        return new self([
            'client_id' => self::environment('GOOGLE_CLIENT_ID'),
            'client_secret' => self::environment('GOOGLE_CLIENT_SECRET'),
            'redirect_uri' => self::environment('GOOGLE_REDIRECT_URI'),
            'token_file' => $tokenFile,
            'cache_file' => $cacheFile !== '' ? $cacheFile : dirname($tokenFile) . DIRECTORY_SEPARATOR . 'google-reviews-cache.json',
            'account_id' => self::environment('GOOGLE_BUSINESS_ACCOUNT_ID'),
            'location_id' => self::environment('GOOGLE_BUSINESS_LOCATION_ID'),
            'location_name' => self::environment('GOOGLE_BUSINESS_LOCATION_NAME') ?: 'ZentDigital',
            'cache_ttl' => self::positiveIntegerEnvironment('GOOGLE_BUSINESS_CACHE_TTL', self::DEFAULT_CACHE_TTL_SECONDS),
        ]);
    }

    /**
     * @return array{
     *   updated_at:string,account_id:string,location_id:string,location_name:string,
     *   average_rating:?float,total_reviews:?int,source:string,stale:bool,
     *   reviews:array<int,array<string,mixed>>
     * }
     */
    public function getReviews(bool $forceRefresh = false): array
    {
        $cached = $this->readCache();
        if (!$forceRefresh && $cached !== null && $this->isFresh($cached)) {
            return $this->withSource($cached, 'cache', false);
        }

        try {
            $accessToken = $this->refreshAccessToken();
            [$accountId, $location] = $this->discoverLocation($accessToken);
            $reviews = $this->fetchReviews($accessToken, $accountId, $location['id']);

            $result = [
                'updated_at' => gmdate('c', ($this->clock)()),
                'account_id' => $accountId,
                'location_id' => $location['id'],
                'location_name' => $location['title'],
                'average_rating' => $reviews['average_rating'],
                'total_reviews' => $reviews['total_reviews'],
                'source' => 'api',
                'stale' => false,
                'reviews' => $reviews['reviews'],
            ];

            $this->writeCache($result);
            return $result;
        } catch (GoogleBusinessProfileException $exception) {
            if ($cached !== null) {
                return $this->withSource($cached, 'fallback', true);
            }

            throw $exception;
        }
    }

    /** @return array{account_found:bool,location_found:bool,location_name:?string,total_reviews:?int,average_rating:?float,updated_at:?string,source:string} */
    public function diagnostics(): array
    {
        try {
            $data = $this->getReviews();
            return [
                'account_found' => $data['account_id'] !== '',
                'location_found' => $data['location_id'] !== '',
                'location_name' => $data['location_name'] !== '' ? $data['location_name'] : null,
                'total_reviews' => $data['total_reviews'],
                'average_rating' => $data['average_rating'],
                'updated_at' => $data['updated_at'],
                'source' => $data['source'],
            ];
        } catch (GoogleBusinessProfileException) {
            return [
                'account_found' => false,
                'location_found' => false,
                'location_name' => null,
                'total_reviews' => null,
                'average_rating' => null,
                'updated_at' => null,
                'source' => 'unavailable',
            ];
        }
    }

    private function refreshAccessToken(): string
    {
        $refreshToken = $this->readRefreshToken();
        $body = http_build_query([
            'grant_type' => 'refresh_token',
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'refresh_token' => $refreshToken,
        ], '', '&', PHP_QUERY_RFC3986);

        $response = $this->send('POST', self::TOKEN_ENDPOINT, [
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
        ], $body);
        $payload = $this->decodeJson($response['body'], 'token');

        if ($response['status'] < 200 || $response['status'] >= 300
            || !isset($payload['access_token'])
            || !is_string($payload['access_token'])
            || $payload['access_token'] === '') {
            throw new GoogleBusinessProfileException('Google token renewal failed.');
        }

        return $payload['access_token'];
    }

    /** @return array{0:string,1:array{id:string,title:string}} */
    private function discoverLocation(string $accessToken): array
    {
        $accounts = $this->fetchAccounts($accessToken);
        $configuredAccount = $this->config['account_id'];
        $matches = [];

        foreach ($accounts as $accountId) {
            if ($configuredAccount !== '' && !hash_equals($configuredAccount, $accountId)) {
                continue;
            }

            foreach ($this->fetchLocations($accessToken, $accountId) as $location) {
                if ($this->locationMatches($location)) {
                    $matches[] = [$accountId, $location];
                }
            }
        }

        if (count($matches) !== 1) {
            throw new GoogleBusinessProfileException('The configured business location could not be identified unambiguously.');
        }

        return $matches[0];
    }

    /** @return array<int,string> */
    private function fetchAccounts(string $accessToken): array
    {
        $accounts = [];
        $pageToken = '';

        do {
            $query = ['pageSize' => 20];
            if ($pageToken !== '') {
                $query['pageToken'] = $pageToken;
            }
            $payload = $this->authorizedJson('GET', self::ACCOUNTS_ENDPOINT . '?' . http_build_query($query), $accessToken);

            foreach (($payload['accounts'] ?? []) as $account) {
                if (!is_array($account) || !isset($account['name']) || !is_string($account['name'])) {
                    continue;
                }
                $accounts[] = $this->resourceId($account['name'], 'accounts');
            }
            $pageToken = isset($payload['nextPageToken']) && is_string($payload['nextPageToken'])
                ? $payload['nextPageToken']
                : '';
        } while ($pageToken !== '');

        if ($accounts === []) {
            throw new GoogleBusinessProfileException('No Google Business Profile account was available.');
        }

        return array_values(array_unique($accounts));
    }

    /** @return array<int,array{id:string,title:string}> */
    private function fetchLocations(string $accessToken, string $accountId): array
    {
        $locations = [];
        $pageToken = '';
        $endpoint = self::BUSINESS_INFORMATION_ENDPOINT . '/accounts/' . rawurlencode($accountId) . '/locations';

        do {
            $query = ['readMask' => 'name,title,storeCode,metadata', 'pageSize' => 100];
            if ($pageToken !== '') {
                $query['pageToken'] = $pageToken;
            }
            $payload = $this->authorizedJson('GET', $endpoint . '?' . http_build_query($query), $accessToken);

            foreach (($payload['locations'] ?? []) as $location) {
                if (!is_array($location)
                    || !isset($location['name'], $location['title'])
                    || !is_string($location['name'])
                    || !is_string($location['title'])
                    || trim($location['title']) === '') {
                    continue;
                }
                $locations[] = [
                    'id' => $this->resourceId($location['name'], 'locations'),
                    'title' => trim($location['title']),
                ];
            }
            $pageToken = isset($payload['nextPageToken']) && is_string($payload['nextPageToken'])
                ? $payload['nextPageToken']
                : '';
        } while ($pageToken !== '');

        return $locations;
    }

    private function locationMatches(array $location): bool
    {
        if ($this->config['location_id'] !== '') {
            return hash_equals($this->config['location_id'], $location['id']);
        }

        return $this->normalizeName($location['title']) === $this->normalizeName($this->config['location_name']);
    }

    /** @return array{average_rating:?float,total_reviews:?int,reviews:array<int,array<string,mixed>>} */
    private function fetchReviews(string $accessToken, string $accountId, string $locationId): array
    {
        $reviews = [];
        $averageRating = null;
        $totalReviews = null;
        $pageToken = '';
        $endpoint = self::REVIEWS_ENDPOINT . '/accounts/' . rawurlencode($accountId)
            . '/locations/' . rawurlencode($locationId) . '/reviews';

        do {
            $query = ['pageSize' => 50, 'orderBy' => 'updateTime desc'];
            if ($pageToken !== '') {
                $query['pageToken'] = $pageToken;
            }
            $payload = $this->authorizedJson('GET', $endpoint . '?' . http_build_query($query), $accessToken);

            if ($averageRating === null && isset($payload['averageRating']) && is_numeric($payload['averageRating'])) {
                $averageRating = (float) $payload['averageRating'];
            }
            if ($totalReviews === null && isset($payload['totalReviewCount']) && is_numeric($payload['totalReviewCount'])) {
                $totalReviews = (int) $payload['totalReviewCount'];
            }

            foreach (($payload['reviews'] ?? []) as $review) {
                if (is_array($review)) {
                    $normalized = $this->normalizeReview($review);
                    if ($normalized !== null) {
                        $reviews[] = $normalized;
                    }
                }
            }
            $pageToken = isset($payload['nextPageToken']) && is_string($payload['nextPageToken'])
                ? $payload['nextPageToken']
                : '';
        } while ($pageToken !== '');

        return [
            'average_rating' => $averageRating,
            'total_reviews' => $totalReviews,
            'reviews' => $reviews,
        ];
    }

    /** @return array<string,mixed>|null */
    private function normalizeReview(array $review): ?array
    {
        $id = isset($review['reviewId']) && is_string($review['reviewId']) ? trim($review['reviewId']) : '';
        if ($id === '' && isset($review['name']) && is_string($review['name'])) {
            $parts = explode('/', trim($review['name'], '/'));
            $id = (string) end($parts);
        }
        if ($id === '') {
            return null;
        }

        $reviewer = isset($review['reviewer']) && is_array($review['reviewer']) ? $review['reviewer'] : [];
        $reply = isset($review['reviewReply']) && is_array($review['reviewReply']) ? $review['reviewReply'] : [];

        return [
            'id' => $id,
            'author' => $this->nullableString($reviewer['displayName'] ?? null),
            'author_photo' => $this->nullableUrl($reviewer['profilePhotoUrl'] ?? null),
            'rating' => $this->rating($review['starRating'] ?? null),
            'comment' => $this->nullableString($review['comment'] ?? null),
            'created_at' => $this->date($review['createTime'] ?? null),
            'updated_at' => $this->date($review['updateTime'] ?? null),
            'owner_reply' => $this->nullableString($reply['comment'] ?? null),
            'owner_reply_updated_at' => $this->date($reply['updateTime'] ?? null),
        ];
    }

    /** @return array<string,mixed> */
    private function authorizedJson(string $method, string $url, string $accessToken): array
    {
        $response = $this->send($method, $url, [
            'Accept: application/json',
            'Authorization: Bearer ' . $accessToken,
        ], null);

        if ($response['status'] < 200 || $response['status'] >= 300) {
            throw new GoogleBusinessProfileException('Google Business Profile returned an unsuccessful response.');
        }

        return $this->decodeJson($response['body'], 'api');
    }

    /** @return array{status:int,body:string} */
    private function send(string $method, string $url, array $headers, ?string $body): array
    {
        try {
            $response = ($this->request)($method, $url, $headers, $body);
        } catch (Throwable) {
            throw new GoogleBusinessProfileException('Secure communication with Google failed.');
        }

        if (!is_array($response)
            || !isset($response['status'], $response['body'])
            || !is_int($response['status'])
            || !is_string($response['body'])) {
            throw new GoogleBusinessProfileException('The HTTP client returned an invalid response.');
        }

        return $response;
    }

    /** @return array{status:int,body:string} */
    private function curlRequest(string $method, string $url, array $headers, ?string $body): array
    {
        if (!extension_loaded('curl')) {
            throw new GoogleBusinessProfileException('The required HTTP client is unavailable.');
        }
        $handle = curl_init($url);
        if ($handle === false) {
            throw new GoogleBusinessProfileException('The HTTP request could not be initialized.');
        }

        curl_setopt_array($handle, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_USERAGENT => 'ZentDigital-Google-Business-Profile/1.0',
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        ]);
        if ($body !== null) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
        }

        $responseBody = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $failed = $responseBody === false;
        curl_close($handle);

        if ($failed) {
            throw new GoogleBusinessProfileException('Secure communication with Google failed.');
        }

        return ['status' => $status, 'body' => (string) $responseBody];
    }

    /** @return array<string,mixed> */
    private function decodeJson(string $json, string $context): array
    {
        try {
            $payload = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new GoogleBusinessProfileException('Google returned invalid JSON for ' . $context . '.');
        }
        if (!is_array($payload)) {
            throw new GoogleBusinessProfileException('Google returned an invalid JSON structure.');
        }
        return $payload;
    }

    private function readRefreshToken(): string
    {
        $contents = @file_get_contents($this->config['token_file']);
        if (!is_string($contents) || $contents === '') {
            throw new GoogleBusinessProfileException('The private refresh token is unavailable.');
        }
        $payload = $this->decodeJson($contents, 'refresh token file');
        $token = $payload['refresh_token'] ?? null;
        if (!is_string($token) || trim($token) === '') {
            throw new GoogleBusinessProfileException('The private refresh token file is incomplete.');
        }
        return trim($token);
    }

    /** @return array<string,mixed>|null */
    private function readCache(): ?array
    {
        if (!is_file($this->config['cache_file'])) {
            return null;
        }
        $contents = $this->readLockedFile($this->config['cache_file']);
        if (!is_string($contents) || $contents === '') {
            return null;
        }
        try {
            $payload = json_decode($contents, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
        return is_array($payload) && $this->validCache($payload) ? $payload : null;
    }

    private function readLockedFile(string $path): string|false
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }
        if (!flock($handle, LOCK_SH)) {
            fclose($handle);
            return false;
        }
        $contents = stream_get_contents($handle);
        flock($handle, LOCK_UN);
        fclose($handle);
        return $contents;
    }

    /** @param array<string,mixed> $data */
    private function writeCache(array $data): void
    {
        $directory = dirname($this->config['cache_file']);
        if (!is_dir($directory) || !is_writable($directory)) {
            throw new GoogleBusinessProfileException('The private cache directory is unavailable.');
        }

        try {
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new GoogleBusinessProfileException('The cache data could not be encoded.');
        }

        $previousUmask = umask(0077);
        $temporaryFile = tempnam($directory, '.zent-google-reviews-');
        umask($previousUmask);
        if ($temporaryFile === false) {
            throw new GoogleBusinessProfileException('The private cache could not be prepared.');
        }

        if (file_put_contents($temporaryFile, $json . PHP_EOL, LOCK_EX) === false) {
            @unlink($temporaryFile);
            throw new GoogleBusinessProfileException('The private cache could not be written.');
        }
        @chmod($temporaryFile, 0600);
        if (!@rename($temporaryFile, $this->config['cache_file'])) {
            // Windows cannot reliably replace an existing file with rename().
            // LOCK_EX keeps the portable fallback from exposing a partial concurrent write.
            if (file_put_contents($this->config['cache_file'], $json . PHP_EOL, LOCK_EX) === false) {
                @unlink($temporaryFile);
                throw new GoogleBusinessProfileException('The private cache could not be finalized.');
            }
            @chmod($this->config['cache_file'], 0600);
            @unlink($temporaryFile);
        }
    }

    /** @param array<string,mixed> $cache */
    private function validCache(array $cache): bool
    {
        if (!isset($cache['updated_at'], $cache['account_id'], $cache['location_id'], $cache['location_name'], $cache['reviews'])
            || !array_key_exists('average_rating', $cache)
            || !array_key_exists('total_reviews', $cache)
            || !array_key_exists('source', $cache)
            || !array_key_exists('stale', $cache)
            || !is_string($cache['updated_at'])
            || strtotime($cache['updated_at']) === false
            || !is_string($cache['account_id'])
            || $cache['account_id'] === ''
            || !is_string($cache['location_id'])
            || $cache['location_id'] === ''
            || !is_string($cache['location_name'])
            || $cache['location_name'] === ''
            || !is_array($cache['reviews'])
            || !(is_null($cache['average_rating'] ?? null) || is_float($cache['average_rating']) || is_int($cache['average_rating']))
            || !(is_null($cache['total_reviews']) || is_int($cache['total_reviews']))
            || (is_numeric($cache['average_rating']) && ((float) $cache['average_rating'] < 1 || (float) $cache['average_rating'] > 5))
            || (is_int($cache['total_reviews']) && $cache['total_reviews'] < 0)
            || !is_string($cache['source'])
            || !is_bool($cache['stale'])) {
            return false;
        }

        foreach ($cache['reviews'] as $review) {
            if (!is_array($review)
                || !isset($review['id'])
                || !is_string($review['id'])
                || $review['id'] === ''
                || !$this->nullableCacheString($review, 'author')
                || !$this->nullableCacheString($review, 'author_photo')
                || !$this->nullableCacheString($review, 'comment')
                || !$this->nullableCacheString($review, 'created_at')
                || !$this->nullableCacheString($review, 'updated_at')
                || !$this->nullableCacheString($review, 'owner_reply')
                || !$this->nullableCacheString($review, 'owner_reply_updated_at')
                || !array_key_exists('rating', $review)
                || !(is_null($review['rating']) || (is_int($review['rating']) && $review['rating'] >= 1 && $review['rating'] <= 5))) {
                return false;
            }
        }
        return true;
    }

    /** @param array<string,mixed> $data */
    private function nullableCacheString(array $data, string $key): bool
    {
        return array_key_exists($key, $data) && (is_null($data[$key]) || is_string($data[$key]));
    }

    /** @param array<string,mixed> $cache */
    private function isFresh(array $cache): bool
    {
        $updatedAt = strtotime($cache['updated_at']);
        if ($updatedAt === false) {
            return false;
        }
        $age = ($this->clock)() - $updatedAt;
        return $age >= 0 && $age < $this->config['cache_ttl'];
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    private function withSource(array $data, string $source, bool $stale): array
    {
        $data['source'] = $source;
        $data['stale'] = $stale;
        return $data;
    }

    /** @param array<string,mixed> $config @return array<string,mixed> */
    private function validateConfiguration(array $config): array
    {
        foreach (['client_id', 'client_secret', 'redirect_uri', 'token_file', 'cache_file'] as $key) {
            if (!isset($config[$key]) || !is_string($config[$key]) || trim($config[$key]) === '') {
                throw new GoogleBusinessProfileException('Google Business Profile configuration is incomplete.');
            }
            $config[$key] = trim($config[$key]);
        }

        $config['account_id'] = $this->optionalId($config['account_id'] ?? '', 'accounts');
        $config['location_id'] = $this->optionalId($config['location_id'] ?? '', 'locations');
        $config['location_name'] = isset($config['location_name']) && is_string($config['location_name'])
            ? trim($config['location_name'])
            : 'ZentDigital';
        if ($config['location_id'] === '' && $config['location_name'] === '') {
            throw new GoogleBusinessProfileException('A business location selector is required.');
        }
        $config['cache_ttl'] = isset($config['cache_ttl']) && is_int($config['cache_ttl']) && $config['cache_ttl'] > 0
            ? $config['cache_ttl']
            : self::DEFAULT_CACHE_TTL_SECONDS;

        $this->validatePrivatePath($config['token_file']);
        $this->validatePrivatePath($config['cache_file']);
        return $config;
    }

    private function validatePrivatePath(string $path): void
    {
        $absolute = str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
        if (!$absolute) {
            throw new GoogleBusinessProfileException('Private Google data paths must be absolute.');
        }
        $directory = realpath(dirname($path));
        if ($directory === false) {
            throw new GoogleBusinessProfileException('A private Google data directory does not exist.');
        }
        $documentRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
        if ($documentRoot !== false) {
            $root = rtrim(strtolower(str_replace('\\', '/', $documentRoot)), '/') . '/';
            $candidate = rtrim(strtolower(str_replace('\\', '/', $directory)), '/') . '/';
            if (str_starts_with($candidate, $root)) {
                throw new GoogleBusinessProfileException('Private Google data must be stored outside the web root.');
            }
        }
    }

    private function optionalId(mixed $value, string $resource): string
    {
        if (!is_string($value) || trim($value) === '') {
            return '';
        }
        return $this->resourceId(trim($value), $resource);
    }

    private function resourceId(string $value, string $resource): string
    {
        $prefix = $resource . '/';
        $id = str_starts_with($value, $prefix) ? substr($value, strlen($prefix)) : $value;
        if ($id === '' || preg_match('/^[A-Za-z0-9_-]+$/', $id) !== 1) {
            throw new GoogleBusinessProfileException('Google returned an invalid resource identifier.');
        }
        return $id;
    }

    private function normalizeName(string $value): string
    {
        $lower = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
        return (string) preg_replace('/[^\p{L}\p{N}]+/u', '', $lower);
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function nullableUrl(mixed $value): ?string
    {
        if (!is_string($value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
            return null;
        }
        return strtolower((string) parse_url($value, PHP_URL_SCHEME)) === 'https' ? $value : null;
    }

    private function rating(mixed $value): ?int
    {
        $ratings = ['ONE' => 1, 'TWO' => 2, 'THREE' => 3, 'FOUR' => 4, 'FIVE' => 5];
        if (is_string($value) && isset($ratings[$value])) {
            return $ratings[$value];
        }
        if (is_numeric($value) && (int) $value >= 1 && (int) $value <= 5) {
            return (int) $value;
        }
        return null;
    }

    private function date(mixed $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }
        try {
            return (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('UTC'))->format('c');
        } catch (Exception) {
            return null;
        }
    }

    private static function environment(string $name): string
    {
        $value = getenv($name);
        return is_string($value) ? trim($value) : '';
    }

    private static function positiveIntegerEnvironment(string $name, int $default): int
    {
        $value = self::environment($name);
        return ctype_digit($value) && (int) $value > 0 ? (int) $value : $default;
    }
}
