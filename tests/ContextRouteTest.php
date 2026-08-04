<?php

declare(strict_types = 1);

use Kirby\Cms\App;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class ContextRouteTest extends ApiRouteTestCase
{
    private function callContextRoute(array $options): mixed
    {
        return $this->callRoute(
            new App(['options' => ['johannschopplich.lingohub' => $options]]),
            '__lingohub__/context'
        );
    }

    #[Test]
    public function reports_configured_options_as_present(): void
    {
        $response = $this->callContextRoute([
            'apiKey' => 'test-key',
            'workspaceId' => 'test-workspace',
            'projectId' => 'test-project',
        ]);

        $this->assertSame([
            'hasApiKey' => true,
            'hasWorkspaceId' => true,
            'hasProjectId' => true,
        ], $response['config']);
    }

    /** @return array<string, array{0: mixed}> */
    public static function unusableOptions(): array
    {
        return [
            'absent' => [null],
            'empty string' => [''],
            'non-string' => [['test-key']],
        ];
    }

    #[Test]
    #[DataProvider('unusableOptions')]
    public function reports_an_unusable_option_as_absent(mixed $value): void
    {
        $response = $this->callContextRoute([
            'apiKey' => $value,
            'workspaceId' => $value,
            'projectId' => $value,
        ]);

        $this->assertSame([
            'hasApiKey' => false,
            'hasWorkspaceId' => false,
            'hasProjectId' => false,
        ], $response['config']);
    }

    #[Test]
    public function never_sends_option_values_to_the_panel(): void
    {
        $response = $this->callContextRoute([
            'apiKey' => 'test-key',
            'workspaceId' => 'test-workspace',
            'projectId' => 'test-project',
        ]);

        // Any Panel user of any role can read this response, so the guard
        // covers the whole envelope rather than the `config` key alone
        $payload = json_encode($response);

        $this->assertStringNotContainsString('test-key', $payload);
        $this->assertStringNotContainsString('test-workspace', $payload);
        $this->assertStringNotContainsString('test-project', $payload);
    }
}
