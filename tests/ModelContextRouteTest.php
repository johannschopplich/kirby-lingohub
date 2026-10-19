<?php

declare(strict_types = 1);

use Kirby\Exception\NotFoundException;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class ModelContextRouteTest extends ApiRouteTestCase
{
    private function callModelContextRoute(string $path): mixed
    {
        $kirby = self::bootApp([
            'languages' => [
                ['code' => 'en', 'name' => 'English', 'default' => true, 'locale' => 'en_US.UTF-8'],
                ['code' => 'de', 'name' => 'Deutsch', 'locale' => 'de_DE'],
                ['code' => 'fr', 'name' => 'Français', 'locale' => 'fr_FR']
            ],
            'request' => [
                'body' => ['path' => $path]
            ],
            'site' => [
                'children' => [
                    [
                        'slug' => 'a-note',
                        'translations' => [
                            ['code' => 'en', 'content' => ['title' => 'A note']],
                            ['code' => 'de', 'content' => ['title' => 'Eine Notiz']]
                        ],
                        'files' => [
                            ['filename' => 'image.jpg']
                        ]
                    ]
                ]
            ]
        ]);

        return $this->callRoute($kirby, '__lingohub__/model-context', 'POST');
    }

    #[Test]
    public function lists_each_language_resource_file_of_a_page(): void
    {
        $response = $this->callModelContextRoute('pages/a-note');

        $this->assertSame([
            'en' => 'a-note/default_en-US.json',
            'de' => 'a-note/default_de-DE.json',
            'fr' => 'a-note/default_fr-FR.json'
        ], $response['resourceFiles']);
    }

    #[Test]
    public function keeps_the_site_resources_under_the_site_path(): void
    {
        $response = $this->callModelContextRoute('site');

        $this->assertSame('site/site_de-DE.json', $response['resourceFiles']['de']);
    }

    #[Test]
    public function keeps_the_file_resources_under_the_file_id(): void
    {
        $response = $this->callModelContextRoute('pages/a-note/files/image.jpg');

        $this->assertSame('a-note/image.jpg/default_de-DE.json', $response['resourceFiles']['de']);
    }

    #[Test]
    public function lists_the_secondary_languages_the_model_has_content_in(): void
    {
        $response = $this->callModelContextRoute('pages/a-note');

        $this->assertSame(['de'], $response['translatedLanguageCodes']);
    }

    #[Test]
    public function throws_for_a_path_without_a_model(): void
    {
        $this->expectException(NotFoundException::class);

        $this->callModelContextRoute('pages/missing');
    }
}
