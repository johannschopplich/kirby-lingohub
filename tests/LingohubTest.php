<?php

declare(strict_types = 1);

use JohannSchopplich\Lingohub\Lingohub;
use Kirby\Exception\AuthException;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Exception\LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class LingohubTest extends ApiRouteTestCase
{
    private static function bootAppWith(string $option, mixed $value): void
    {
        self::bootApp([
            'options' => [
                'johannschopplich.lingohub' => [
                    'apiKey' => 'test-key',
                    'workspaceId' => 'test-workspace',
                    'projectId' => 'test-project',
                    $option => $value,
                ],
            ],
        ]);
    }

    #[Test]
    public function throws_when_the_api_key_is_missing(): void
    {
        self::bootAppWith('apiKey', null);

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Missing Lingohub API key');

        new Lingohub();
    }

    /** @return array<string, array{0: string, 1: mixed}> */
    public static function unusableProjectOptions(): array
    {
        return [
            'missing workspaceId' => ['workspaceId', null],
            'missing projectId' => ['projectId', null],
            // Without the guard, a non-string reaches the typed property assignment and raises a `TypeError`.
            'mistyped workspaceId' => ['workspaceId', ['test-workspace']],
            'mistyped projectId' => ['projectId', 0],
        ];
    }

    #[Test]
    #[DataProvider('unusableProjectOptions')]
    public function throws_when_a_project_option_is_unusable(string $option, mixed $value): void
    {
        self::bootAppWith($option, $value);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required option "johannschopplich.lingohub.' . $option . '"');

        new Lingohub();
    }

    #[Test]
    public function reports_the_descriptions_of_a_lingohub_error_response(): void
    {
        $exception = self::requestFailure(
            401,
            '{"error":null,"validationErrors":[{"errors":[{"code":"invalid","description":"Invalid API key","parameters":{}}],"field":"token"}]}'
        );

        $this->assertSame('Lingohub API request failed: Invalid API key', $exception->getMessage());
    }

    #[Test]
    public function answers_a_lingohub_error_with_a_bad_gateway_status(): void
    {
        $exception = self::requestFailure(401, '');

        $this->assertSame(502, $exception->getHttpCode());
        $this->assertSame('Lingohub API request failed: HTTP 401', $exception->getMessage());
    }

    #[Test]
    public function keeps_the_braces_of_an_unrecognized_response_body(): void
    {
        $exception = self::requestFailure(502, "<html><body>\n  Bad gateway {\"retry\":true}\n</body></html>");

        $this->assertSame('Lingohub API request failed: Bad gateway {"retry":true}', $exception->getMessage());
    }

    private static function requestFailure(int $code, string $body): LogicException
    {
        return (new ReflectionMethod(Lingohub::class, 'requestFailure'))->invoke(null, $code, $body);
    }
}
