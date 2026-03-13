<?php

declare(strict_types = 1);

use JohannSchopplich\Lingohub\FieldTypeResolver;
use Kirby\Cms\App;
use PHPUnit\Framework\TestCase;

final class FieldTypeResolverTest extends TestCase
{
    protected App $app;

    protected function setUp(): void
    {
        $this->app = new App([
            'fields' => [
                'custom-writer' => [
                    'extends' => 'writer',
                ],
                'custom-files' => [
                    'extends' => 'files',
                ],
                'deep-custom' => [
                    'extends' => 'custom-writer',
                ],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        App::destroy();
    }

    public function testResolveBaseTypeReturnsKnownTypeAsIs(): void
    {
        $this->assertSame('text', FieldTypeResolver::resolveBaseType('text'));
        $this->assertSame('blocks', FieldTypeResolver::resolveBaseType('blocks'));
        $this->assertSame('files', FieldTypeResolver::resolveBaseType('files'));
        $this->assertSame('writer', FieldTypeResolver::resolveBaseType('writer'));
    }

    public function testResolveBaseTypeResolvesCustomTypeToBaseType(): void
    {
        $this->assertSame('writer', FieldTypeResolver::resolveBaseType('custom-writer'));
        $this->assertSame('files', FieldTypeResolver::resolveBaseType('custom-files'));
    }

    public function testResolveBaseTypeResolvesMultiLevelExtendsChain(): void
    {
        // deep-custom → custom-writer → writer
        $this->assertSame('writer', FieldTypeResolver::resolveBaseType('deep-custom'));
    }

    public function testResolveBaseTypeReturnsUnknownTypeAsIs(): void
    {
        $result = @FieldTypeResolver::resolveBaseType('nonexistent-field-type');
        $this->assertSame('nonexistent-field-type', $result);
    }

    public function testNormalizeFieldsResolvesCustomTypesInNestedFields(): void
    {
        $fields = [
            'title' => ['type' => 'text'],
            'content' => [
                'type' => 'structure',
                'fields' => [
                    'heading' => ['type' => 'text'],
                    'body' => ['type' => 'custom-writer'],
                ]
            ],
        ];

        $normalized = FieldTypeResolver::normalizeFields($fields);

        $this->assertSame('writer', $normalized['content']['fields']['body']['type']);
    }

    public function testNormalizeFieldsResolvesCustomTypesInFieldsetTabs(): void
    {
        $fields = [
            'blocks' => [
                'type' => 'blocks',
                'fieldsets' => [
                    'myblock' => [
                        'tabs' => [
                            'content' => [
                                'fields' => [
                                    'text' => ['type' => 'custom-writer'],
                                    'image' => ['type' => 'custom-files'],
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $normalized = FieldTypeResolver::normalizeFields($fields);

        $blockFields = $normalized['blocks']['fieldsets']['myblock']['tabs']['content']['fields'];
        $this->assertSame('writer', $blockFields['text']['type']);
        $this->assertSame('files', $blockFields['image']['type']);
    }

    public function testNormalizeFieldsPreservesNonTypeProperties(): void
    {
        $fields = [
            'text' => [
                'type' => 'text',
                'translate' => true,
                'translateInKirbyOnly' => false,
            ],
            'image' => [
                'type' => 'files',
                'translateInKirbyOnly' => true,
            ],
        ];

        $normalized = FieldTypeResolver::normalizeFields($fields);

        $this->assertTrue($normalized['text']['translate']);
        $this->assertFalse($normalized['text']['translateInKirbyOnly']);
        $this->assertTrue($normalized['image']['translateInKirbyOnly']);
    }
}
