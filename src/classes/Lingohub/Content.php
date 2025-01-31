<?php

declare(strict_types = 1);

namespace JohannSchopplich\Lingohub;

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
        $this->model = Model::resolveModel($modelId);
    }

    public function uploadTranslation(string $languageCode)
    {
        $lingohub = Lingohub::instance();
        $filename = Lingohub::resolveResourceFilename($this->model, $languageCode);

        return $lingohub->uploadResource(
            $this->model->id(),
            $filename,
            $this->serializeContent($languageCode)
        );
    }

    public function downloadTranslation(string $languageCode, array $options = [])
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
        });
    }

    public function serializeContent(string $languageCode): array
    {
        $content = $this->model->content($languageCode)->toArray();
        $fields = Model::resolveModelFields($this->model);
        return $this->resolveTranslatableContent($content, $fields);
    }

    public function deserializeContent(array $translation, string $languageCode): array
    {
        $content = $this->model->content($languageCode)->toArray();
        $fields = Model::resolveModelFields($this->model);
        return $this->mergeTranslatedContent($translation, $content, $fields);
    }

    private function resolveTranslatableContent(array &$obj, array $fields, string $prefix = ''): array
    {
        $result = [];

        foreach ($obj as $key => $value) {
            if (!isset($fields[$key])) {
                continue;
            }
            if (!$fields[$key]['translate']) {
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

            // Handle text-like fields
            if (in_array($fields[$key]['type'], ['list', 'tags', 'text', 'textarea', 'writer', 'markdown'], true)) {
                if ($fields[$key]['type'] === 'writer' && is_string($value)) {
                    $result[$fieldKey] = $this->stripWriterTags($value);
                } else {
                    $result[$fieldKey] = $value;
                }
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

    private function isBlockTranslatable(array $block): bool
    {
        return isset($block['content']) &&
            A::isAssociative($block['content'])
            && isset($block['id'])
            && ($block['isHidden'] ?? false) !== true;
    }

    private function flattenTabFields(array $fieldsets, array $block): array
    {
        $blockFields = [];

        foreach ($fieldsets[$block['type']]['tabs'] as $tab) {
            $blockFields = array_merge($blockFields, $tab['fields']);
        }

        return $blockFields;
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
                if ($fields[$fieldName]['type'] === 'writer') {
                    $result[$fieldName] = $this->restoreWriterTags($value);
                } elseif (in_array($fields[$fieldName]['type'], ['list', 'tags', 'text', 'textarea', 'markdown'], true)) {
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
                $this->mergeBlockContent($result[$fieldName], $parts, $value);
            } elseif ($fields[$fieldName]['type'] === 'layout') {
                $this->mergeLayoutContent($result[$fieldName], $parts, $value);
            } elseif ($fields[$fieldName]['type'] === 'structure') {
                $this->mergeStructureContent($result[$fieldName], $parts, $value, $fields[$fieldName]['fields']);
            } elseif ($fields[$fieldName]['type'] === 'object') {
                $this->mergeObjectContent($result[$fieldName], $parts, $value);
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

    private function mergeBlockContent(array &$blocks, array $parts, string $value): void
    {
        if (count($parts) < 2) {
            return;
        }

        $blockId = $parts[0];
        $blockType = $parts[1];
        $fieldName = $parts[2] ?? null;

        foreach ($blocks as &$block) {
            if ($block['id'] === $blockId && $block['type'] === $blockType && $fieldName) {
                $block['content'][$fieldName] = $value;
                break;
            }
        }
    }

    private function mergeLayoutContent(array &$layouts, array $parts, string $value): void
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
                    if ($block['id'] === $blockId && $block['type'] === $blockType && $fieldName) {
                        $block['content'][$fieldName] = $value;
                        break 3;
                    }
                }
            }
        }
    }

    private function mergeStructureContent(array &$items, array $parts, string $value, array $fields): void
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
        if (isset($fields[$fieldName])) {
            $items[$index][$fieldName] = $value;
        }
    }

    private function mergeObjectContent(array &$object, array $parts, string $value): void
    {
        if (empty($parts)) {
            return;
        }

        $fieldName = array_shift($parts);
        if (empty($parts)) {
            $object[$fieldName] = $value;
        }
    }

    private function stripWriterTags(string $content): string
    {
        // First handle multiple paragraphs
        $content = str_replace('</p><p>', "\n\n", $content);

        // Then strip any remaining `p` tags
        $content = preg_replace('/^<p>|<\/p>$/', '', $content);

        return trim($content);
    }

    private function restoreWriterTags(string $content): string
    {
        // Split content by double newlines
        $segments = explode("\n\n", $content);

        // If we have multiple segments, wrap each in `p` tags
        if (count($segments) > 1) {
            $segments = array_map(
                fn ($segment) => '<p>' . trim($segment) . '</p>',
                $segments
            );
            return implode('', $segments);
        }

        // For single segments, just wrap in `p` tags
        return '<p>' . trim($content) . '</p>';
    }
}
