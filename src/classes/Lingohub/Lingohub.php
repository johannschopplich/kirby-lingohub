<?php

declare(strict_types = 1);

namespace JohannSchopplich\Lingohub;

use Kirby\Cms\App;
use Kirby\Cms\ModelWithContent;
use Kirby\Cms\Site;
use Kirby\Exception\AuthException;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Exception\LogicException;
use Kirby\Http\Remote;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Str;

final class Lingohub
{
    public const API_URL = 'https://api.lingohub.com/v1';

    private const OPTION_PREFIX = 'johannschopplich.lingohub.';

    private readonly string $apiKey;
    private readonly string $workspaceId;
    private readonly string $projectId;
    private static self|null $instance;

    public function __construct()
    {
        $kirby = App::instance();
        $apiKey = $kirby->option(self::OPTION_PREFIX . 'apiKey');

        if (!self::isUsableOption($apiKey)) {
            throw new AuthException('Missing Lingohub API key');
        }

        $this->apiKey = $apiKey;
        $this->workspaceId = self::requireOption($kirby, 'workspaceId');
        $this->projectId = self::requireOption($kirby, 'projectId');
    }

    public static function instance(): Lingohub
    {
        return self::$instance ??= new self();
    }

    public static function hasUsableOption(string $name): bool
    {
        return self::isUsableOption(App::instance()->option(self::OPTION_PREFIX . $name));
    }

    public static function resolveResourcePath(ModelWithContent $model): string
    {
        return $model instanceof Site ? 'site' : $model->id();
    }

    public static function resolveResourceFilename(ModelWithContent $model, string $languageCode): string
    {
        $kirby = App::instance();
        /** @var \Kirby\Cms\Language|null */
        $language = $kirby->languages()->find($languageCode);

        $localeCode = $language?->locale(LC_ALL) ?? $languageCode;
        // Support ISO 3166-1 Alpha-2 and ISO 639-1 codes:
        // (1) Convert locale code to IETF language tag format (e.g. `en_US` to `en-US`).
        $localeCode = str_replace('_', '-', $localeCode);
        // (2) Remove UTF-8 suffix for consistency.
        $localeCode = preg_replace('/\.utf-?8$/i', '', $localeCode);

        $blueprintName = $model->blueprint()->name();

        if (str_starts_with($blueprintName, 'pages/') || str_starts_with($blueprintName, 'files/')) {
            $blueprintName = substr($blueprintName, 6);
        }

        return "{$blueprintName}_{$localeCode}.json";
    }

    public function getTranslationStatus(): array
    {
        return $this->request($this->getProjectPath('status'));
    }

    public function uploadResource(string $path, string $filename, array $data): array
    {
        // `Remote` URL-encodes array data, so the multipart body is built by hand.
        $boundary = '----' . uniqid('boundary', true);
        $body = "--{$boundary}\r\n"
            . "Content-Disposition: form-data; name=\"file\"; filename=\"{$filename}\"\r\n"
            . "Content-Type: application/json\r\n\r\n"
            . json_encode($data) . "\r\n"
            . "--{$boundary}--\r\n";

        return $this->request($this->getProjectPath('resources', ['path' => $path]), [
            'method' => 'POST',
            'headers' => [
                'Content-Type' => "multipart/form-data; boundary={$boundary}"
            ],
            'data' => $body
        ]);
    }

    public function downloadResource(string $path, string $filename, array $options = []): array
    {
        return $this->request($this->getProjectPath(
            "resources/{$filename}",
            A::merge(['path' => $path], $options)
        ));
    }

    /**
     * Every option is arbitrary user input, so anything but a
     * non-empty string is a misconfiguration.
     */
    private static function isUsableOption(mixed $value): bool
    {
        return is_string($value) && $value !== '';
    }

    private static function requireOption(App $kirby, string $name): string
    {
        $value = $kirby->option(self::OPTION_PREFIX . $name);

        if (!self::isUsableOption($value)) {
            // TODO: Drop K4 compat in v2 – use named arg (`message:`) once Kirby 5 is the floor.
            throw new InvalidArgumentException(
                'Missing required option "' . self::OPTION_PREFIX . $name . '"'
            );
        }

        return $value;
    }

    private function getProjectPath(string $path, array $query = []): string
    {
        $encodedQuery = http_build_query($query);
        $resolvedPath = "{$this->workspaceId}/projects/{$this->projectId}/{$path}";
        return $encodedQuery === '' ? $resolvedPath : "{$resolvedPath}?{$encodedQuery}";
    }

    private function request(string $path, array $options = []): array
    {
        $response = new Remote(self::API_URL . '/' . $path, A::merge([
            'headers' => [
                'Accept' => '*/*',
                'Authorization' => 'Bearer ' . $this->apiKey,
            ]
        ], $options));

        if ($response->code() < 200 || $response->code() >= 300) {
            throw self::requestFailure($response->code(), $response->content());
        }

        return $response->json();
    }

    private static function requestFailure(int|null $code, string|null $body): LogicException
    {
        $error = json_decode($body ?? '', true);
        $reasons = [];

        if (is_array($error)) {
            if (is_string($error['error'] ?? null) && $error['error'] !== '') {
                $reasons[] = $error['error'];
            }

            foreach ($error['validationErrors'] ?? [] as $validationError) {
                foreach ($validationError['errors'] ?? [] as $fieldError) {
                    if (is_string($fieldError['description'] ?? null) && $fieldError['description'] !== '') {
                        $reasons[] = $fieldError['description'];
                    }
                }
            }
        }

        $reason = $reasons !== []
            ? implode('; ', array_unique($reasons))
            : Str::short(trim(preg_replace('/\s+/', ' ', strip_tags($body ?? ''))), 200);

        // The reason goes in as data: Kirby resolves `{…}` in the message as placeholders,
        // and a substituted value isn't parsed again.
        // TODO: Drop K4 compat in v2 – use named args (`fallback:`, `data:`, `httpCode:`) once Kirby 5 is the floor.
        return new LogicException([
            'fallback' => 'Lingohub API request failed: { reason }',
            'data' => ['reason' => $reason !== '' ? $reason : "HTTP {$code}"],
            'httpCode' => $code
        ]);
    }
}
