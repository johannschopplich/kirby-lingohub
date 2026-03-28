<?php

declare(strict_types = 1);

use JohannSchopplich\Lingohub\Content;
use Kirby\Cms\App;
use Kirby\Data\Json;
use Kirby\Data\Yaml;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ContentTest extends TestCase
{
    protected App $app;

    protected function setUp(): void
    {
        $this->app = new App([
            'languages' => [
                [
                    'code' => 'en',
                    'name' => 'English',
                    'default' => true
                ],
                [
                    'code' => 'de',
                    'name' => 'Deutsch'
                ]
            ],
            'blueprints' => [
                'pages/default' => [
                    'fields' => [
                        'text' => [
                            'type' => 'textarea',
                            'translate' => true
                        ],
                        'untranslatableText' => [
                            'type' => 'text',
                            'translate' => false
                        ],
                        'blocks' => [
                            'type' => 'blocks',
                            'translate' => true,
                            'fieldsets' => [
                                'text' => [
                                    'tabs' => [
                                        'content' => [
                                            'fields' => [
                                                'text' => [
                                                    'type' => 'textarea',
                                                    'translate' => true
                                                ]
                                            ]
                                        ]
                                    ]
                                ],
                                'heading' => [
                                    'tabs' => [
                                        'content' => [
                                            'fields' => [
                                                'text' => [
                                                    'type' => 'text',
                                                    'translate' => true
                                                ],
                                                'level' => [
                                                    'type' => 'select',
                                                    'translate' => false
                                                ]
                                            ]
                                        ]
                                    ]
                                ],
                                'container' => [
                                    'tabs' => [
                                        'content' => [
                                            'fields' => [
                                                'innerblocks' => [
                                                    'type' => 'blocks',
                                                    'translate' => true,
                                                    'fieldsets' => [
                                                        'heading' => [
                                                            'tabs' => [
                                                                'content' => [
                                                                    'fields' => [
                                                                        'text' => [
                                                                            'type' => 'text',
                                                                            'translate' => true
                                                                        ],
                                                                        'level' => [
                                                                            'type' => 'select',
                                                                            'translate' => false
                                                                        ]
                                                                    ]
                                                                ]
                                                            ]
                                                        ],
                                                        'text' => [
                                                            'tabs' => [
                                                                'content' => [
                                                                    'fields' => [
                                                                        'text' => [
                                                                            'type' => 'textarea',
                                                                            'translate' => true
                                                                        ]
                                                                    ]
                                                                ]
                                                            ]
                                                        ],
                                                        'imageblock' => [
                                                            'tabs' => [
                                                                'content' => [
                                                                    'fields' => [
                                                                        'text' => [
                                                                            'type' => 'textarea',
                                                                            'translate' => true
                                                                        ],
                                                                        'image' => [
                                                                            'type' => 'files',
                                                                            'translateInKirbyOnly' => true
                                                                        ]
                                                                    ]
                                                                ]
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ],
                                'imageblock' => [
                                    'tabs' => [
                                        'content' => [
                                            'fields' => [
                                                'text' => [
                                                    'type' => 'textarea',
                                                    'translate' => true
                                                ],
                                                'image' => [
                                                    'type' => 'files',
                                                    'translateInKirbyOnly' => true
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'structure' => [
                            'type' => 'structure',
                            'translate' => true,
                            'fields' => [
                                'heading' => [
                                    'type' => 'text',
                                    'translate' => true
                                ],
                                'description' => [
                                    'type' => 'textarea',
                                    'translate' => true
                                ],
                                'note' => [
                                    'type' => 'text',
                                    'translateInKirbyOnly' => true
                                ]
                            ]
                        ],
                        'object' => [
                            'type' => 'object',
                            'translate' => true,
                            'fields' => [
                                'title' => [
                                    'type' => 'text',
                                    'translate' => true
                                ],
                                'description' => [
                                    'type' => 'textarea',
                                    'translate' => true
                                ]
                            ]
                        ],
                        'image' => [
                            'type' => 'files',
                            'translateInKirbyOnly' => true
                        ],
                        'kirbyOnlyText' => [
                            'type' => 'text',
                            'translateInKirbyOnly' => true
                        ],
                        'layoutfield' => [
                            'type' => 'layout',
                            'translate' => true,
                            'fieldsets' => [
                                'imageblock' => [
                                    'tabs' => [
                                        'content' => [
                                            'fields' => [
                                                'text' => [
                                                    'type' => 'textarea',
                                                    'translate' => true
                                                ],
                                                'image' => [
                                                    'type' => 'files',
                                                    'translateInKirbyOnly' => true
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'site' => [
                'children' => [
                    [
                        'slug' => 'test',
                        'template' => 'default',
                        'translations' => [
                            [
                                'code' => 'en',
                                'content' => [
                                    'title' => 'Test Page',
                                    'text' => 'Hello world',
                                    'untranslatableText' => 'Do not translate',
                                    'blocks' => Json::encode([
                                        [
                                            'type' => 'text',
                                            'id' => 'block1',
                                            'content' => [
                                                'text' => 'Block text content'
                                            ]
                                        ],
                                        [
                                            'type' => 'heading',
                                            'id' => 'block2',
                                            'content' => [
                                                'text' => 'Block heading',
                                                'level' => 'h2'
                                            ]
                                        ],
                                        [
                                            'type' => 'text',
                                            'id' => 'block3',
                                            'isHidden' => true,
                                            'content' => [
                                                'text' => 'Hidden block'
                                            ]
                                        ],
                                        [
                                            'type' => 'container',
                                            'id' => 'container1',
                                            'content' => [
                                                'innerblocks' => Json::encode([
                                                    [
                                                        'type' => 'heading',
                                                        'id' => 'inner1',
                                                        'content' => [
                                                            'text' => 'Inner heading',
                                                            'level' => 'h3'
                                                        ]
                                                    ],
                                                    [
                                                        'type' => 'text',
                                                        'id' => 'inner2',
                                                        'content' => [
                                                            'text' => 'Inner text content'
                                                        ]
                                                    ],
                                                    [
                                                        'type' => 'imageblock',
                                                        'id' => 'innerimg1',
                                                        'content' => [
                                                            'text' => 'Inner image caption',
                                                            'image' => '- file://en-inner-image.jpg'
                                                        ]
                                                    ]
                                                ])
                                            ]
                                        ],
                                        [
                                            'type' => 'imageblock',
                                            'id' => 'imgblock1',
                                            'content' => [
                                                'text' => 'Image caption',
                                                'image' => '- file://en-block-image.jpg'
                                            ]
                                        ]
                                    ]),
                                    'structure' => Yaml::encode([
                                        [
                                            'heading' => 'Section 1',
                                            'description' => 'Description 1',
                                            'note' => 'EN Note 1'
                                        ],
                                        [
                                            'heading' => 'Section 2',
                                            'description' => 'Description 2',
                                            'note' => 'EN Note 2'
                                        ]
                                    ]),
                                    'object' => Yaml::encode([
                                        'title' => 'Object title',
                                        'description' => 'Object description'
                                    ]),
                                    'image' => '- file://image-en.jpg',
                                    'kirbyOnlyText' => 'Only translate in Kirby',
                                    'layoutfield' => Json::encode([
                                        [
                                            'id' => 'layout1',
                                            'columns' => [
                                                [
                                                    'blocks' => [
                                                        [
                                                            'type' => 'imageblock',
                                                            'id' => 'layoutimg1',
                                                            'content' => [
                                                                'text' => 'Layout image caption',
                                                                'image' => '- file://en-layout-image.jpg'
                                                            ]
                                                        ]
                                                    ],
                                                    'width' => '1/1'
                                                ]
                                            ]
                                        ]
                                    ])
                                ]
                            ],
                            [
                                'code' => 'de',
                                'content' => [
                                    'title' => 'Testseite',
                                    'text' => 'Hallo Welt',
                                    'image' => '- file://image-de.jpg',
                                    'kirbyOnlyText' => 'Nur in Kirby übersetzen',
                                    'structure' => Yaml::encode([
                                        [
                                            'heading' => 'DE Abschnitt 1',
                                            'description' => 'DE Beschreibung 1',
                                            'note' => 'DE Notiz 1'
                                        ],
                                        [
                                            'heading' => 'DE Abschnitt 2',
                                            'description' => 'DE Beschreibung 2',
                                            'note' => 'DE Notiz 2'
                                        ]
                                    ]),
                                    'blocks' => Json::encode([
                                        [
                                            'type' => 'text',
                                            'id' => 'block1',
                                            'content' => [
                                                'text' => 'DE Block text content'
                                            ]
                                        ],
                                        [
                                            'type' => 'heading',
                                            'id' => 'block2',
                                            'content' => [
                                                'text' => 'DE Block heading',
                                                'level' => 'h2'
                                            ]
                                        ],
                                        [
                                            'type' => 'text',
                                            'id' => 'block3',
                                            'isHidden' => true,
                                            'content' => [
                                                'text' => 'DE Hidden block'
                                            ]
                                        ],
                                        [
                                            'type' => 'container',
                                            'id' => 'container1',
                                            'content' => [
                                                'innerblocks' => Json::encode([
                                                    [
                                                        'type' => 'heading',
                                                        'id' => 'inner1',
                                                        'content' => [
                                                            'text' => 'DE Inner heading',
                                                            'level' => 'h3'
                                                        ]
                                                    ],
                                                    [
                                                        'type' => 'text',
                                                        'id' => 'inner2',
                                                        'content' => [
                                                            'text' => 'DE Inner text content'
                                                        ]
                                                    ],
                                                    [
                                                        'type' => 'imageblock',
                                                        'id' => 'innerimg1',
                                                        'content' => [
                                                            'text' => 'DE Inner image caption',
                                                            'image' => '- file://de-inner-image.jpg'
                                                        ]
                                                    ]
                                                ])
                                            ]
                                        ],
                                        [
                                            'type' => 'imageblock',
                                            'id' => 'imgblock1',
                                            'content' => [
                                                'text' => 'DE Image caption',
                                                'image' => '- file://de-block-image.jpg'
                                            ]
                                        ]
                                    ]),
                                    'layoutfield' => Json::encode([
                                        [
                                            'id' => 'layout1',
                                            'columns' => [
                                                [
                                                    'blocks' => [
                                                        [
                                                            'type' => 'imageblock',
                                                            'id' => 'layoutimg1',
                                                            'content' => [
                                                                'text' => 'DE Layout image caption',
                                                                'image' => '- file://de-layout-image.jpg'
                                                            ]
                                                        ]
                                                    ],
                                                    'width' => '1/1'
                                                ]
                                            ]
                                        ]
                                    ])
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'options' => [
                'debug' => true
            ]
        ]);
    }

    protected function tearDown(): void
    {
        App::destroy();
    }

    // --- Serialization tests ---

    #[Test]
    public function serialize_simple_text_field(): void
    {
        $content = new Content('test');
        $serialized = $content->serializeContent('en');

        $this->assertArrayHasKey('text', $serialized);
        $this->assertSame('Hello world', $serialized['text']);
    }

    #[Test]
    public function serialize_blocks_field(): void
    {
        $content = new Content('test');
        $serialized = $content->serializeContent('en');

        $this->assertArrayHasKey('blocks_block1_text_text', $serialized);
        $this->assertSame('Block text content', $serialized['blocks_block1_text_text']);

        $this->assertArrayHasKey('blocks_block2_heading_text', $serialized);
        $this->assertSame('Block heading', $serialized['blocks_block2_heading_text']);

        // Level (select) should be excluded (`translate: false`)
        $this->assertArrayNotHasKey('blocks_block2_heading_level', $serialized);
    }

    #[Test]
    public function serialize_skips_untranslatable_fields(): void
    {
        $content = new Content('test');
        $serialized = $content->serializeContent('en');

        $this->assertArrayNotHasKey('untranslatableText', $serialized);
    }

    #[Test]
    public function serialize_skips_hidden_blocks(): void
    {
        $content = new Content('test');
        $serialized = $content->serializeContent('en');

        $this->assertArrayNotHasKey('blocks_block3_text_text', $serialized);
    }

    #[Test]
    public function serialize_structure_field(): void
    {
        $content = new Content('test');
        $serialized = $content->serializeContent('en');

        $this->assertArrayHasKey('structure_0_heading', $serialized);
        $this->assertSame('Section 1', $serialized['structure_0_heading']);

        $this->assertArrayHasKey('structure_1_description', $serialized);
        $this->assertSame('Description 2', $serialized['structure_1_description']);
    }

    #[Test]
    public function serialize_object_field(): void
    {
        $content = new Content('test');
        $serialized = $content->serializeContent('en');

        $this->assertArrayHasKey('object_title', $serialized);
        $this->assertSame('Object title', $serialized['object_title']);

        $this->assertArrayHasKey('object_description', $serialized);
        $this->assertSame('Object description', $serialized['object_description']);
    }

    #[Test]
    public function serialize_nested_blocks_in_custom_block(): void
    {
        $content = new Content('test');
        $serialized = $content->serializeContent('en');

        // Nested blocks should produce deep keys:
        // blocks_{containerId}_{containerType}_{innerFieldName}_{innerBlockId}_{innerBlockType}_{contentField}
        $this->assertArrayHasKey('blocks_container1_container_innerblocks_inner1_heading_text', $serialized);
        $this->assertSame('Inner heading', $serialized['blocks_container1_container_innerblocks_inner1_heading_text']);

        $this->assertArrayHasKey('blocks_container1_container_innerblocks_inner2_text_text', $serialized);
        $this->assertSame('Inner text content', $serialized['blocks_container1_container_innerblocks_inner2_text_text']);

        // Level in nested heading should be excluded
        $this->assertArrayNotHasKey('blocks_container1_container_innerblocks_inner1_heading_level', $serialized);
    }

    #[Test]
    public function serialize_skips_translate_in_kirby_only_fields(): void
    {
        $content = new Content('test');
        $serialized = $content->serializeContent('en');

        // Top-level files field with `translateInKirbyOnly` is already excluded by isTextLikeField,
        // but a text field with `translateInKirbyOnly` should also be excluded
        $this->assertArrayNotHasKey('kirbyOnlyText', $serialized);

        // The image field (files type) should also not appear
        $this->assertArrayNotHasKey('image', $serialized);
    }

    // --- Deserialization tests ---

    #[Test]
    public function deserialize_simple_text_field(): void
    {
        $content = new Content('test');
        $deserialized = $content->deserializeContent([
            'text' => 'Hallo Welt'
        ], 'de');

        $this->assertSame('Hallo Welt', $deserialized['text']);
    }

    #[Test]
    public function deserialize_blocks_field(): void
    {
        $content = new Content('test');
        $deserialized = $content->deserializeContent([
            'blocks_block1_text_text' => 'Blocktext Inhalt',
            'blocks_block2_heading_text' => 'Block Überschrift'
        ], 'de');

        $blocks = Json::decode($deserialized['blocks']);

        // Block IDs and types must be preserved
        $this->assertSame('block1', $blocks[0]['id']);
        $this->assertSame('text', $blocks[0]['type']);
        $this->assertSame('Blocktext Inhalt', $blocks[0]['content']['text']);

        $this->assertSame('block2', $blocks[1]['id']);
        $this->assertSame('heading', $blocks[1]['type']);
        $this->assertSame('Block Überschrift', $blocks[1]['content']['text']);
        // Non-translated fields should be preserved from default language
        $this->assertSame('h2', $blocks[1]['content']['level']);
    }

    #[Test]
    public function deserialize_structure_field(): void
    {
        $content = new Content('test');
        $deserialized = $content->deserializeContent([
            'structure_0_heading' => 'Abschnitt 1',
            'structure_0_description' => 'Beschreibung 1',
            'structure_1_heading' => 'Abschnitt 2',
            'structure_1_description' => 'Beschreibung 2'
        ], 'de');

        $structure = Yaml::decode($deserialized['structure']);
        $this->assertSame('Abschnitt 1', $structure[0]['heading']);
        $this->assertSame('Beschreibung 1', $structure[0]['description']);
        $this->assertSame('Abschnitt 2', $structure[1]['heading']);
        $this->assertSame('Beschreibung 2', $structure[1]['description']);
    }

    #[Test]
    public function deserialize_object_field(): void
    {
        $content = new Content('test');
        $deserialized = $content->deserializeContent([
            'object_title' => 'Objekt Titel',
            'object_description' => 'Objekt Beschreibung'
        ], 'de');

        $object = Yaml::decode($deserialized['object']);
        $this->assertSame('Objekt Titel', $object['title']);
        $this->assertSame('Objekt Beschreibung', $object['description']);
    }

    #[Test]
    public function deserialize_nested_blocks_in_custom_block(): void
    {
        $content = new Content('test');
        $deserialized = $content->deserializeContent([
            'blocks_container1_container_innerblocks_inner1_heading_text' => 'Innere Überschrift',
            'blocks_container1_container_innerblocks_inner2_text_text' => 'Innerer Text'
        ], 'de');

        $blocks = Json::decode($deserialized['blocks']);

        // Find the container block
        $containerBlock = null;
        foreach ($blocks as $block) {
            if ($block['id'] === 'container1') {
                $containerBlock = $block;
                break;
            }
        }

        $this->assertNotNull($containerBlock, 'Container block must exist');
        $this->assertSame('container', $containerBlock['type']);

        $innerBlocks = Json::decode($containerBlock['content']['innerblocks']);

        $this->assertCount(3, $innerBlocks);

        // Inner heading block
        $this->assertSame('inner1', $innerBlocks[0]['id']);
        $this->assertSame('heading', $innerBlocks[0]['type']);
        $this->assertSame('Innere Überschrift', $innerBlocks[0]['content']['text']);
        $this->assertSame('h3', $innerBlocks[0]['content']['level']);

        // Inner text block
        $this->assertSame('inner2', $innerBlocks[1]['id']);
        $this->assertSame('text', $innerBlocks[1]['type']);
        $this->assertSame('Innerer Text', $innerBlocks[1]['content']['text']);
    }

    #[Test]
    public function round_trip_nested_blocks(): void
    {
        $content = new Content('test');

        $serialized = $content->serializeContent('en');

        // Verify expected keys exist
        $this->assertArrayHasKey('blocks_container1_container_innerblocks_inner1_heading_text', $serialized);
        $this->assertArrayHasKey('blocks_container1_container_innerblocks_inner2_text_text', $serialized);

        // Simulate translation by prefixing values
        $translated = [];
        foreach ($serialized as $key => $value) {
            $translated[$key] = '[de]' . $value;
        }

        $deserialized = $content->deserializeContent($translated, 'de');
        $blocks = Json::decode($deserialized['blocks']);

        // Find container
        $containerBlock = null;
        foreach ($blocks as $block) {
            if ($block['id'] === 'container1') {
                $containerBlock = $block;
                break;
            }
        }

        $this->assertNotNull($containerBlock);
        $innerBlocks = Json::decode($containerBlock['content']['innerblocks']);

        $this->assertSame('[de]Inner heading', $innerBlocks[0]['content']['text']);
        $this->assertSame('heading', $innerBlocks[0]['type']);
        $this->assertSame('h3', $innerBlocks[0]['content']['level']);

        $this->assertSame('[de]Inner text content', $innerBlocks[1]['content']['text']);
        $this->assertSame('text', $innerBlocks[1]['type']);
    }

    // --- translateInKirbyOnly tests ---

    #[Test]
    public function deserialize_preserves_translate_in_kirby_only_top_level(): void
    {
        $content = new Content('test');

        $deserialized = $content->deserializeContent([
            'text' => 'Hallo Welt'
        ], 'de');

        $this->assertSame('- file://image-de.jpg', $deserialized['image']);
    }

    #[Test]
    public function deserialize_falls_back_to_default_language_for_non_flagged_fields(): void
    {
        $content = new Content('test');

        // Non-flagged fields should fall back to default language content as base
        // when no translation is provided
        $deserialized = $content->deserializeContent([], 'de');

        // Text should come from the default language (EN in this test setup)
        $this->assertSame('Hello world', $deserialized['text']);
    }

    #[Test]
    public function deserialize_preserves_translate_in_kirby_only_in_blocks(): void
    {
        $content = new Content('test');

        // Translate the text field of the `imageblock`, but the `image` field
        // should be restored from DE (`translateInKirbyOnly`: true)
        $deserialized = $content->deserializeContent([
            'blocks_imgblock1_imageblock_text' => 'DE Bildunterschrift'
        ], 'de');

        $blocks = Json::decode($deserialized['blocks']);

        // Find the imageblock
        $imageBlock = null;
        foreach ($blocks as $block) {
            if ($block['id'] === 'imgblock1') {
                $imageBlock = $block;
                break;
            }
        }

        $this->assertNotNull($imageBlock, 'Image block must exist');
        $this->assertSame('imageblock', $imageBlock['type']);

        // Text should be translated
        $this->assertSame('DE Bildunterschrift', $imageBlock['content']['text']);

        // Image should be restored from DE, not EN default
        $this->assertSame('- file://de-block-image.jpg', $imageBlock['content']['image']);
    }

    #[Test]
    public function deserialize_preserves_translate_in_kirby_only_in_nested_blocks(): void
    {
        $content = new Content('test');

        // Translate the nested `imageblock`'s text, but image should be restored from DE
        $deserialized = $content->deserializeContent([
            'blocks_container1_container_innerblocks_innerimg1_imageblock_text' => 'DE Innere Bildunterschrift'
        ], 'de');

        $blocks = Json::decode($deserialized['blocks']);

        // Find the container block
        $containerBlock = null;
        foreach ($blocks as $block) {
            if ($block['id'] === 'container1') {
                $containerBlock = $block;
                break;
            }
        }

        $this->assertNotNull($containerBlock, 'Container block must exist');
        $innerBlocks = Json::decode($containerBlock['content']['innerblocks']);

        // Find the inner imageblock
        $innerImageBlock = null;
        foreach ($innerBlocks as $block) {
            if ($block['id'] === 'innerimg1') {
                $innerImageBlock = $block;
                break;
            }
        }

        $this->assertNotNull($innerImageBlock, 'Inner image block must exist');
        $this->assertSame('imageblock', $innerImageBlock['type']);

        // Text should be translated
        $this->assertSame('DE Innere Bildunterschrift', $innerImageBlock['content']['text']);

        // Image should be restored from DE, not EN default
        $this->assertSame('- file://de-inner-image.jpg', $innerImageBlock['content']['image']);
    }

    #[Test]
    public function deserialize_preserves_translate_in_kirby_only_in_layout_blocks(): void
    {
        $content = new Content('test');

        // Translate the layout `imageblock`'s text, but image should be restored from DE
        $deserialized = $content->deserializeContent([
            'layoutfield_layoutimg1_imageblock_text' => 'DE Layout Bildunterschrift'
        ], 'de');

        $layouts = Json::decode($deserialized['layoutfield']);

        $this->assertNotEmpty($layouts);
        $this->assertSame('layout1', $layouts[0]['id']);

        $layoutBlocks = $layouts[0]['columns'][0]['blocks'];
        $this->assertNotEmpty($layoutBlocks);

        $imageBlock = $layoutBlocks[0];
        $this->assertSame('layoutimg1', $imageBlock['id']);
        $this->assertSame('imageblock', $imageBlock['type']);

        // Text should be translated
        $this->assertSame('DE Layout Bildunterschrift', $imageBlock['content']['text']);

        // Image should be restored from DE, not EN default
        $this->assertSame('- file://de-layout-image.jpg', $imageBlock['content']['image']);
    }

    #[Test]
    public function deserialize_preserves_translate_in_kirby_only_top_level_alongside_block_translations(): void
    {
        $content = new Content('test');

        // Regression test: verify top-level `translateInKirbyOnly` still works
        // after the refactor to recursive restoration
        $deserialized = $content->deserializeContent([
            'text' => 'Neuer Text',
            'blocks_imgblock1_imageblock_text' => 'Bildunterschrift'
        ], 'de');

        // Top-level image should be DE value
        $this->assertSame('- file://image-de.jpg', $deserialized['image']);

        // Text should be translated
        $this->assertSame('Neuer Text', $deserialized['text']);
    }

    #[Test]
    public function deserialize_preserves_translate_in_kirby_only_in_structure(): void
    {
        $content = new Content('test');

        // Translate structure fields, but note (`translateInKirbyOnly`) should be preserved from DE
        $deserialized = $content->deserializeContent([
            'structure_0_heading' => 'Neuer Abschnitt 1',
            'structure_0_description' => 'Neue Beschreibung 1'
        ], 'de');

        $structure = Yaml::decode($deserialized['structure']);

        // Translated fields should be updated
        $this->assertSame('Neuer Abschnitt 1', $structure[0]['heading']);
        $this->assertSame('Neue Beschreibung 1', $structure[0]['description']);

        // `translateInKirbyOnly` field should be restored from DE
        $this->assertSame('DE Notiz 1', $structure[0]['note']);
        $this->assertSame('DE Notiz 2', $structure[1]['note']);
    }
}
