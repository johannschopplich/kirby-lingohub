<?php

declare(strict_types = 1);

namespace JohannSchopplich\Lingohub;

use JohannSchopplich\KirbyTools\FieldResolver;
use JohannSchopplich\KirbyTools\ModelResolver;
use Kirby\Cms\App;
use Kirby\Cms\File;
use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Data\Data;
use Kirby\Toolkit\A;

final class Content
{
    private Site|Page|File $model;

    public function __construct(string $modelId)
    {
        $this->model = ModelResolver::resolveFromId($modelId);
    }

    /**
     * Uploads the serialized content for the given language to Lingohub.
     */
    public function uploadTranslation(string $languageCode): array
    {
        $lingohub = Lingohub::instance();
        $filename = Lingohub::resolveResourceFilename($this->model, $languageCode);

        return $lingohub->uploadResource(
            $this->model->id(),
            $filename,
            $this->serializeContent($languageCode)
        );
    }

    /**
     * Downloads the translation for the given language from Lingohub
     * and updates the model content.
     */
    public function downloadTranslation(string $languageCode, array $options = []): void
    {
        $lingohub = Lingohub::instance();
        $filename = Lingohub::resolveResourceFilename($this->model, $languageCode);

        $serializedContent = $lingohub->downloadResource(
            $this->model->id(),
            $filename,
            $options
        );

        App::instance()->impersonate('kirby', function () use ($languageCode, $serializedContent) {
            $mergedContent = $this->deserializeContent($serializedContent, $languageCode);

            // Write the translated and merged new content
            $this->model = $this->model->update($mergedContent, $languageCode);

            // Handle title translation if present
            if (isset($serializedContent['title']) && method_exists($this->model, 'changeTitle')) {
                $this->model = $this->model->changeTitle($serializedContent['title'], $languageCode);
            }
        });
    }

    /**
     * Flattens the model's content into a key-value map for Lingohub.
     */
    public function serializeContent(string $languageCode): array
    {
        $content = $this->model->content($languageCode)->toArray();
        $fields = FieldResolver::resolveModelFields($this->model);
        $fields = FieldTypeResolver::normalizeFields($fields);
        $serializedContent = $this->resolveTranslatableContent($content, $fields);

        // Add title to translatable content if the model has one
        if (method_exists($this->model, 'title')) {
            $title = $this->model->title($languageCode)->value();
            $serializedContent['title'] = $title;
        }

        return $serializedContent;
    }

    /**
     * Merges a Lingohub key-value map back into the model's content structure.
     */
    public function deserializeContent(array $serializedContent, string $languageCode): array
    {
        $defaultLanguageCode = App::instance()->defaultLanguage()->code();
        // Explicitly use the default language content as a base to merge the translation into,
        // as the translation might not contain all segments (e.g. in blocks or layouts)
        $content = $this->model->content($defaultLanguageCode)->toArray();
        $fields = FieldResolver::resolveModelFields($this->model);
        $fields = FieldTypeResolver::normalizeFields($fields);

        // Remove title from translation array, as it's handled separately
        unset($serializedContent['title']);

        // Preserve the existing slug from the target language
        $currentSlug = $this->model->content($languageCode)->get('slug')->value();

        $deserializedContent = $this->mergeTranslatedContent($serializedContent, $content, $fields);

        // Restore the original slug for this language if it exists
        if ($currentSlug !== null) {
            $deserializedContent['slug'] = $currentSlug;
        }

        // Restore target language values for fields flagged with `translateInKirbyOnly`
        $targetContent = $this->model->content($languageCode)->toArray();
        $this->restoreKirbyOnlyFieldsInContent($deserializedContent, $targetContent, $fields);

        return $deserializedContent;
    }

    private function resolveTranslatableContent(array &$obj, array $fields, string $prefix = ''): array
    {
        $result = [];

        foreach ($obj as $key => $value) {
            if (!isset($fields[$key])) {
                continue;
            }

            if (!($fields[$key]['translate'] ?? true)) {
                continue;
            }

            if ($fields[$key]['translateInKirbyOnly'] ?? false) {
                continue;
            }

            // Parse JSON-encoded fields
            if (($fields[$key]['type'] === 'blocks' || $fields[$key]['type'] === 'layout') && is_string($obj[$key])) {
                $obj[$key] = Data::decode($obj[$key], 'json');
            }

            // Parse YAML-encoded fields
            elseif (($fields[$key]['type'] === 'structure' || $fields[$key]['type'] === 'object') && is_string($obj[$key])) {
                $obj[$key] = Data::decode($obj[$key], 'yaml');
            }

            $fieldKey = $prefix ? $prefix . '_' . $key : $key;

            // Handle text-like fields (including custom types that extend them)
            if ($this->isTextLikeField($fields[$key])) {
                $result[$fieldKey] = $value;
            }

            // Handle structure fields
            elseif ($fields[$key]['type'] === 'structure' && is_array($obj[$key])) {
                foreach ($obj[$key] as $index => $item) {
                    $structurePrefix = $fieldKey . '_' . $index;
                    $result = array_merge(
                        $result,
                        $this->resolveTranslatableContent($item, $fields[$key]['fields'], $structurePrefix)
                    );
                }
            }

            // Handle object fields
            elseif ($fields[$key]['type'] === 'object' && A::isAssociative($obj[$key])) {
                $result = array_merge(
                    $result,
                    $this->resolveTranslatableContent($obj[$key], $fields[$key]['fields'], $fieldKey)
                );
            }

            // Handle layout fields
            elseif ($fields[$key]['type'] === 'layout' && is_array($obj[$key])) {
                foreach ($obj[$key] as $layout) {
                    foreach ($layout['columns'] as $column) {
                        foreach ($column['blocks'] as $block) {
                            if ($this->isBlockTranslatable($block) && isset($fields[$key]['fieldsets'][$block['type']])) {
                                $blockPrefix = $fieldKey . '_' . $block['id'] . '_' . $block['type'];
                                $blockFields = $this->flattenTabFields($fields[$key]['fieldsets'], $block);
                                $result = array_merge(
                                    $result,
                                    $this->resolveTranslatableContent($block['content'], $blockFields, $blockPrefix)
                                );
                            }
                        }
                    }
                }
            }

            // Handle block fields
            elseif ($fields[$key]['type'] === 'blocks' && is_array($obj[$key])) {
                foreach ($obj[$key] as $block) {
                    if ($this->isBlockTranslatable($block) && isset($fields[$key]['fieldsets'][$block['type']])) {
                        $blockPrefix = $fieldKey . '_' . $block['id'] . '_' . $block['type'];
                        $blockFields = $this->flattenTabFields($fields[$key]['fieldsets'], $block);
                        $result = array_merge(
                            $result,
                            $this->resolveTranslatableContent($block['content'], $blockFields, $blockPrefix)
                        );
                    }
                }
            }
        }

        return $result;
    }

    private function mergeTranslatedContent(array $translation, array $original, array $fields): array
    {
        $result = $original;

        foreach ($translation as $key => $value) {
            $parts = explode('_', $key);
            $fieldName = array_shift($parts);

            if (!isset($fields[$fieldName])) {
                continue;
            }

            // Direct assignment for simple field types
            if (empty($parts)) {
                if ($this->isTextLikeField($fields[$fieldName])) {
                    $result[$fieldName] = $value;
                }
                continue;
            }

            // Ensure the original field value is decoded
            if (($fields[$fieldName]['type'] === 'blocks' || $fields[$fieldName]['type'] === 'layout') && is_string($result[$fieldName])) {
                $result[$fieldName] = Data::decode($result[$fieldName], 'json');
            } elseif (($fields[$fieldName]['type'] === 'structure' || $fields[$fieldName]['type'] === 'object') && is_string($result[$fieldName])) {
                $result[$fieldName] = Data::decode($result[$fieldName], 'yaml');
            }

            // Handle nested structures
            if ($fields[$fieldName]['type'] === 'blocks') {
                $this->mergeBlockContent($result[$fieldName], $parts, $value, $fields[$fieldName]['fieldsets'] ?? []);
            } elseif ($fields[$fieldName]['type'] === 'layout') {
                $this->mergeLayoutContent($result[$fieldName], $parts, $value, $fields[$fieldName]['fieldsets'] ?? []);
            } elseif ($fields[$fieldName]['type'] === 'structure') {
                $this->mergeStructureContent($result[$fieldName], $parts, $value, $fields[$fieldName]['fields']);
            } elseif ($fields[$fieldName]['type'] === 'object') {
                $this->mergeObjectContent($result[$fieldName], $parts, $value, $fields[$fieldName]['fields']);
            }

            // Re-encode the field value
            if ($fields[$fieldName]['type'] === 'blocks' || $fields[$fieldName]['type'] === 'layout') {
                $result[$fieldName] = Data::encode($result[$fieldName], 'json');
            } elseif ($fields[$fieldName]['type'] === 'structure' || $fields[$fieldName]['type'] === 'object') {
                $result[$fieldName] = Data::encode($result[$fieldName], 'yaml');
            }
        }

        return $result;
    }

    private function mergeBlockContent(array &$blocks, array $parts, string $value, array $fieldsets = []): void
    {
        if (count($parts) < 2) {
            return;
        }

        $blockId = $parts[0];
        $blockType = $parts[1];
        $fieldName = $parts[2] ?? null;

        foreach ($blocks as &$block) {
            if ($block['id'] !== $blockId || $block['type'] !== $blockType || !$fieldName) {
                continue;
            }

            // Simple case: direct field assignment
            if (count($parts) === 3) {
                $block['content'][$fieldName] = $value;
                break;
            }

            // Recursive case: nested field within a block's content
            $remainingParts = array_slice($parts, 3);
            $blockFields = isset($fieldsets[$blockType])
                ? $this->flattenTabFields($fieldsets, $block)
                : [];

            if (!isset($blockFields[$fieldName])) {
                break;
            }

            $fieldType = $blockFields[$fieldName]['type'];

            if ($fieldType === 'blocks') {
                $nestedData = is_string($block['content'][$fieldName])
                    ? Data::decode($block['content'][$fieldName], 'json')
                    : ($block['content'][$fieldName] ?? []);

                $this->mergeBlockContent($nestedData, $remainingParts, $value, $blockFields[$fieldName]['fieldsets'] ?? []);
                $block['content'][$fieldName] = Data::encode($nestedData, 'json');
            } elseif ($fieldType === 'layout') {
                $nestedData = is_string($block['content'][$fieldName])
                    ? Data::decode($block['content'][$fieldName], 'json')
                    : ($block['content'][$fieldName] ?? []);

                $this->mergeLayoutContent($nestedData, $remainingParts, $value, $blockFields[$fieldName]['fieldsets'] ?? []);
                $block['content'][$fieldName] = Data::encode($nestedData, 'json');
            } elseif ($fieldType === 'structure') {
                $nestedData = is_string($block['content'][$fieldName])
                    ? Data::decode($block['content'][$fieldName], 'yaml')
                    : ($block['content'][$fieldName] ?? []);

                $this->mergeStructureContent($nestedData, $remainingParts, $value, $blockFields[$fieldName]['fields'] ?? []);
                $block['content'][$fieldName] = Data::encode($nestedData, 'yaml');
            } elseif ($fieldType === 'object') {
                $nestedData = is_string($block['content'][$fieldName])
                    ? Data::decode($block['content'][$fieldName], 'yaml')
                    : ($block['content'][$fieldName] ?? []);

                $this->mergeObjectContent($nestedData, $remainingParts, $value, $blockFields[$fieldName]['fields'] ?? []);
                $block['content'][$fieldName] = Data::encode($nestedData, 'yaml');
            }

            break;
        }
    }

    private function mergeLayoutContent(array &$layouts, array $parts, string $value, array $fieldsets = []): void
    {
        if (count($parts) < 2) {
            return;
        }

        $blockId = $parts[0];
        $blockType = $parts[1];
        $fieldName = $parts[2] ?? null;

        foreach ($layouts as &$layout) {
            foreach ($layout['columns'] as &$column) {
                foreach ($column['blocks'] as &$block) {
                    if ($block['id'] !== $blockId || $block['type'] !== $blockType || !$fieldName) {
                        continue;
                    }

                    // Simple case: direct field assignment
                    if (count($parts) === 3) {
                        $block['content'][$fieldName] = $value;
                        break 3;
                    }

                    // Recursive case: nested field within a block's content
                    $remainingParts = array_slice($parts, 3);
                    $blockFields = isset($fieldsets[$blockType])
                        ? $this->flattenTabFields($fieldsets, $block)
                        : [];

                    if (!isset($blockFields[$fieldName])) {
                        break 3;
                    }

                    $fieldType = $blockFields[$fieldName]['type'];

                    if ($fieldType === 'blocks') {
                        $nestedData = is_string($block['content'][$fieldName])
                            ? Data::decode($block['content'][$fieldName], 'json')
                            : ($block['content'][$fieldName] ?? []);

                        $this->mergeBlockContent($nestedData, $remainingParts, $value, $blockFields[$fieldName]['fieldsets'] ?? []);
                        $block['content'][$fieldName] = Data::encode($nestedData, 'json');
                    } elseif ($fieldType === 'layout') {
                        $nestedData = is_string($block['content'][$fieldName])
                            ? Data::decode($block['content'][$fieldName], 'json')
                            : ($block['content'][$fieldName] ?? []);

                        $this->mergeLayoutContent($nestedData, $remainingParts, $value, $blockFields[$fieldName]['fieldsets'] ?? []);
                        $block['content'][$fieldName] = Data::encode($nestedData, 'json');
                    } elseif ($fieldType === 'structure') {
                        $nestedData = is_string($block['content'][$fieldName])
                            ? Data::decode($block['content'][$fieldName], 'yaml')
                            : ($block['content'][$fieldName] ?? []);

                        $this->mergeStructureContent($nestedData, $remainingParts, $value, $blockFields[$fieldName]['fields'] ?? []);
                        $block['content'][$fieldName] = Data::encode($nestedData, 'yaml');
                    } elseif ($fieldType === 'object') {
                        $nestedData = is_string($block['content'][$fieldName])
                            ? Data::decode($block['content'][$fieldName], 'yaml')
                            : ($block['content'][$fieldName] ?? []);

                        $this->mergeObjectContent($nestedData, $remainingParts, $value, $blockFields[$fieldName]['fields'] ?? []);
                        $block['content'][$fieldName] = Data::encode($nestedData, 'yaml');
                    }

                    break 3;
                }
            }
        }
    }

    private function mergeStructureContent(array &$items, array $parts, string $value, array $fields = []): void
    {
        if (count($parts) < 1) {
            return;
        }

        $index = array_shift($parts);
        if (!isset($items[$index])) {
            return;
        }

        if (empty($parts)) {
            return;
        }

        $fieldName = array_shift($parts);

        // Direct field assignment if there are no more parts
        if (empty($parts)) {
            if (isset($fields[$fieldName])) {
                $items[$index][$fieldName] = $value;
            }
            return;
        }

        // Handle nested fields within structure items
        if (isset($fields[$fieldName])) {
            $fieldType = $fields[$fieldName]['type'];

            // Handle nested layout fields
            if ($fieldType === 'layout') {
                // Decode the layout JSON if it's a string
                if (is_string($items[$index][$fieldName])) {
                    $layoutData = Data::decode($items[$index][$fieldName], 'json');
                } else {
                    $layoutData = $items[$index][$fieldName] ?? [];
                }

                // Process layout with remaining parts
                $this->mergeLayoutContent($layoutData, $parts, $value, $fields[$fieldName]['fieldsets'] ?? []);

                // Re-encode the layout data
                $items[$index][$fieldName] = Data::encode($layoutData, 'json');
            }
            // Handle nested blocks fields
            elseif ($fieldType === 'blocks') {
                // Decode the blocks JSON if it's a string
                if (is_string($items[$index][$fieldName])) {
                    $blocksData = Data::decode($items[$index][$fieldName], 'json');
                } else {
                    $blocksData = $items[$index][$fieldName] ?? [];
                }

                // Process blocks with remaining parts
                $this->mergeBlockContent($blocksData, $parts, $value, $fields[$fieldName]['fieldsets'] ?? []);

                // Re-encode the blocks data
                $items[$index][$fieldName] = Data::encode($blocksData, 'json');
            }
            // Handle nested structure fields
            elseif ($fieldType === 'structure' || $fieldType === 'object') {
                // Decode the nested structure/object data if it's a string
                if (is_string($items[$index][$fieldName])) {
                    $nestedData = Data::decode($items[$index][$fieldName], 'yaml');
                } else {
                    $nestedData = $items[$index][$fieldName] ?? [];
                }

                // Process structure recursively
                if ($fieldType === 'structure') {
                    $this->mergeStructureContent($nestedData, $parts, $value, $fields[$fieldName]['fields']);
                } else {
                    $this->mergeObjectContent($nestedData, $parts, $value, $fields[$fieldName]['fields']);
                }

                // Re-encode the structure/object data
                $items[$index][$fieldName] = Data::encode($nestedData, 'yaml');
            }
        }
    }

    private function mergeObjectContent(array &$object, array $parts, string $value, array $fields = []): void
    {
        if (empty($parts)) {
            return;
        }

        $fieldName = array_shift($parts);

        // Direct field assignment if there are no more parts
        if (empty($parts)) {
            $object[$fieldName] = $value;
            return;
        }

        // Handle nested fields within object items
        if (isset($object[$fieldName])) {
            $fieldType = $fields[$fieldName]['type'];

            // Handle nested layout fields
            if ($fieldType === 'layout') {
                // Decode the layout JSON if it's a string
                if (is_string($object[$fieldName])) {
                    $layoutData = Data::decode($object[$fieldName], 'json');
                } else {
                    $layoutData = $object[$fieldName];
                }

                // Process layout with remaining parts
                $this->mergeLayoutContent($layoutData, $parts, $value, $fields[$fieldName]['fieldsets'] ?? []);

                // Re-encode the layout data
                $object[$fieldName] = Data::encode($layoutData, 'json');
            }
            // Handle nested blocks fields
            elseif ($fieldType === 'blocks') {
                // Decode the blocks JSON if it's a string
                if (is_string($object[$fieldName])) {
                    $blocksData = Data::decode($object[$fieldName], 'json');
                } else {
                    $blocksData = $object[$fieldName];
                }

                // Process blocks with remaining parts
                $this->mergeBlockContent($blocksData, $parts, $value, $fields[$fieldName]['fieldsets'] ?? []);

                // Re-encode the blocks data
                $object[$fieldName] = Data::encode($blocksData, 'json');
            }
            // Handle nested structure fields
            elseif ($fieldType === 'structure') {
                // Decode the nested structure data if it's a string
                if (is_string($object[$fieldName])) {
                    $nestedData = Data::decode($object[$fieldName], 'yaml');
                } else {
                    $nestedData = $object[$fieldName];
                }

                // Process structure recursively with the fields definition
                $this->mergeStructureContent($nestedData, $parts, $value, $fields[$fieldName]['fields']);

                // Re-encode the structure data
                $object[$fieldName] = Data::encode($nestedData, 'yaml');
            }
            // Handle nested object fields
            elseif ($fieldType === 'object') {
                // Decode the nested object data if it's a string
                if (is_string($object[$fieldName])) {
                    $nestedData = Data::decode($object[$fieldName], 'yaml');
                } else {
                    $nestedData = $object[$fieldName];
                }

                // Process object recursively with the fields definition
                $this->mergeObjectContent($nestedData, $parts, $value, $fields[$fieldName]['fields']);

                // Re-encode the object data
                $object[$fieldName] = Data::encode($nestedData, 'yaml');
            }
        }
    }

    /**
     * Recursively restores `translateInKirbyOnly` field values from the target language content.
     */
    private function restoreKirbyOnlyFieldsInContent(
        array &$mergedContent,
        array $targetContent,
        array $fields
    ): void {
        foreach ($fields as $key => $field) {
            if ($field['translateInKirbyOnly'] ?? false) {
                if (isset($targetContent[$key])) {
                    $mergedContent[$key] = $targetContent[$key];
                }
                continue;
            }

            if (!isset($mergedContent[$key]) || !isset($targetContent[$key])) {
                continue;
            }

            if ($field['type'] === 'blocks') {
                $mergedBlocks = is_string($mergedContent[$key])
                    ? Data::decode($mergedContent[$key], 'json')
                    : $mergedContent[$key];
                $targetBlocks = is_string($targetContent[$key])
                    ? Data::decode($targetContent[$key], 'json')
                    : $targetContent[$key];

                $this->restoreKirbyOnlyFieldsInBlocks($mergedBlocks, $targetBlocks, $field['fieldsets'] ?? []);

                $mergedContent[$key] = is_string($mergedContent[$key])
                    ? Data::encode($mergedBlocks, 'json')
                    : $mergedBlocks;
            } elseif ($field['type'] === 'layout') {
                $mergedLayouts = is_string($mergedContent[$key])
                    ? Data::decode($mergedContent[$key], 'json')
                    : $mergedContent[$key];
                $targetLayouts = is_string($targetContent[$key])
                    ? Data::decode($targetContent[$key], 'json')
                    : $targetContent[$key];

                $targetLayoutsById = [];
                foreach ($targetLayouts as $layout) {
                    $targetLayoutsById[$layout['id']] = $layout;
                }

                foreach ($mergedLayouts as &$layout) {
                    if (!isset($targetLayoutsById[$layout['id']])) {
                        continue;
                    }
                    $targetLayout = $targetLayoutsById[$layout['id']];
                    foreach ($layout['columns'] as $colIndex => &$column) {
                        if (!isset($targetLayout['columns'][$colIndex]['blocks'])) {
                            continue;
                        }
                        $this->restoreKirbyOnlyFieldsInBlocks(
                            $column['blocks'],
                            $targetLayout['columns'][$colIndex]['blocks'],
                            $field['fieldsets'] ?? []
                        );
                    }
                }

                $mergedContent[$key] = is_string($mergedContent[$key])
                    ? Data::encode($mergedLayouts, 'json')
                    : $mergedLayouts;
            } elseif ($field['type'] === 'structure') {
                $mergedItems = is_string($mergedContent[$key])
                    ? Data::decode($mergedContent[$key], 'yaml')
                    : $mergedContent[$key];
                $targetItems = is_string($targetContent[$key])
                    ? Data::decode($targetContent[$key], 'yaml')
                    : $targetContent[$key];

                foreach ($mergedItems as $index => &$item) {
                    if (!isset($targetItems[$index])) {
                        continue;
                    }
                    $this->restoreKirbyOnlyFieldsInContent($item, $targetItems[$index], $field['fields'] ?? []);
                }

                $mergedContent[$key] = is_string($mergedContent[$key])
                    ? Data::encode($mergedItems, 'yaml')
                    : $mergedItems;
            } elseif ($field['type'] === 'object') {
                $mergedObj = is_string($mergedContent[$key])
                    ? Data::decode($mergedContent[$key], 'yaml')
                    : $mergedContent[$key];
                $targetObj = is_string($targetContent[$key])
                    ? Data::decode($targetContent[$key], 'yaml')
                    : $targetContent[$key];

                $this->restoreKirbyOnlyFieldsInContent($mergedObj, $targetObj, $field['fields'] ?? []);

                $mergedContent[$key] = is_string($mergedContent[$key])
                    ? Data::encode($mergedObj, 'yaml')
                    : $mergedObj;
            }
        }
    }

    /**
     * Restores `translateInKirbyOnly` field values within blocks
     * by matching blocks between merged and target content by ID.
     */
    private function restoreKirbyOnlyFieldsInBlocks(
        array &$mergedBlocks,
        array $targetBlocks,
        array $fieldsets
    ): void {
        $targetBlocksById = [];
        foreach ($targetBlocks as $block) {
            if (isset($block['id'])) {
                $targetBlocksById[$block['id']] = $block;
            }
        }

        foreach ($mergedBlocks as &$block) {
            if (!isset($block['id'], $block['type'], $block['content'])) {
                continue;
            }

            if (!isset($fieldsets[$block['type']])) {
                continue;
            }

            $targetBlock = $targetBlocksById[$block['id']] ?? null;
            if ($targetBlock === null) {
                continue;
            }

            $blockFields = $this->flattenTabFields($fieldsets, $block);
            $this->restoreKirbyOnlyFieldsInContent($block['content'], $targetBlock['content'] ?? [], $blockFields);
        }
    }

    private function isBlockTranslatable(array $block): bool
    {
        return isset($block['content']) &&
            A::isAssociative($block['content']) &&
            isset($block['id']) &&
            ($block['isHidden'] ?? false) !== true;
    }

    private function isTextLikeField(array $field): bool
    {
        static $textLikeTypes = ['list', 'tags', 'text', 'textarea', 'writer', 'markdown'];
        return in_array($field['type'] ?? '', $textLikeTypes, true);
    }

    private function flattenTabFields(array $fieldsets, array $block): array
    {
        $blockFields = [];

        foreach ($fieldsets[$block['type']]['tabs'] as $tab) {
            $blockFields = array_merge($blockFields, $tab['fields']);
        }

        return $blockFields;
    }
}
