<?php

declare(strict_types = 1);

use Kirby\Cms\App;
use Kirby\Exception\PermissionException;
use PHPUnit\Framework\Attributes\Test;

final class TranslateExternalOnlyTest extends AppTestCase
{
    private const FIELDS = [
        'teaser' => [
            'type' => 'text',
            'label' => ['en' => 'Teaser', 'de' => 'Anrisstext'],
            'translateExternalOnly' => true
        ],
        'metaTitle' => [
            'type' => 'text',
            'translateExternalOnly' => true
        ],
        'body' => [
            'type' => 'text'
        ]
    ];

    private const TRANSLATIONS = [
        ['code' => 'en', 'content' => ['title' => 'Test', 'teaser' => 'Teaser EN', 'metaTitle' => 'Meta EN', 'body' => 'Body EN']],
        ['code' => 'de', 'content' => ['title' => 'Test', 'teaser' => 'Teaser DE', 'metaTitle' => 'Meta DE', 'body' => 'Body DE']]
    ];

    protected function setUp(): void
    {
        self::bootApp([
            'languages' => [
                ['code' => 'en', 'name' => 'English', 'default' => true],
                ['code' => 'de', 'name' => 'Deutsch']
            ],
            'blueprints' => [
                'pages/default' => ['fields' => self::FIELDS],
                'files/default' => ['fields' => self::FIELDS],
                'site' => ['fields' => self::FIELDS],
                'users/editor' => ['name' => 'editor']
            ],
            'users' => [
                ['email' => 'admin@example.com', 'role' => 'admin'],
                ['email' => 'editor@example.com', 'role' => 'editor']
            ],
            'site' => [
                'translations' => self::TRANSLATIONS,
                'children' => [
                    [
                        'slug' => 'test',
                        'translations' => self::TRANSLATIONS,
                        'files' => [
                            [
                                'filename' => 'test.jpg',
                                'translations' => self::TRANSLATIONS
                            ]
                        ]
                    ]
                ]
            ]
        ]);
    }

    #[Test]
    public function rejects_a_translation_of_an_external_only_page_field(): void
    {
        App::instance()->impersonate('editor@example.com');

        $this->expectException(PermissionException::class);
        $this->expectExceptionMessage('"Teaser"');

        page('test')->update(['teaser' => 'Neuer Anrisstext'], 'de');
    }

    #[Test]
    public function rejects_a_translation_of_an_external_only_site_field(): void
    {
        App::instance()->impersonate('editor@example.com');

        $this->expectException(PermissionException::class);

        site()->update(['teaser' => 'Neuer Anrisstext'], 'de');
    }

    #[Test]
    public function rejects_a_translation_of_an_external_only_file_field(): void
    {
        App::instance()->impersonate('editor@example.com');

        $this->expectException(PermissionException::class);

        page('test')->file('test.jpg')->update(['teaser' => 'Neuer Anrisstext'], 'de');
    }

    #[Test]
    public function rejects_a_translation_of_a_camel_case_external_only_field(): void
    {
        App::instance()->impersonate('editor@example.com');

        $this->expectException(PermissionException::class);

        page('test')->update(['metaTitle' => 'Neuer Metatitel'], 'de');
    }

    #[Test]
    public function lets_other_fields_be_translated(): void
    {
        App::instance()->impersonate('editor@example.com');

        $page = page('test')->update(['body' => 'Neuer Text'], 'de');

        $this->assertSame('Neuer Text', $page->content('de')->get('body')->value());
    }

    #[Test]
    public function lets_the_default_language_be_edited(): void
    {
        App::instance()->impersonate('editor@example.com');

        $page = page('test')->update(['teaser' => 'New teaser'], 'en');

        $this->assertSame('New teaser', $page->content('en')->get('teaser')->value());
    }

    #[Test]
    public function lets_admins_translate_external_only_fields(): void
    {
        App::instance()->impersonate('admin@example.com');

        $page = page('test')->update(['teaser' => 'Neuer Anrisstext'], 'de');

        $this->assertSame('Neuer Anrisstext', $page->content('de')->get('teaser')->value());
    }
}
