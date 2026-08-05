<?php

declare(strict_types = 1);

use JohannSchopplich\Lingohub\Lingohub;
use Kirby\Cms\App;
use Kirby\Exception\AuthException;
use Kirby\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class LingohubTest extends TestCase
{
    protected function tearDown(): void
    {
        App::destroy();
    }

    private static function bootAppWith(string $option, mixed $value): void
    {
        new App([
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
}
