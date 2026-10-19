<?php

declare(strict_types = 1);

namespace JohannSchopplich\Lingohub;

use Closure;
use JohannSchopplich\KirbyTools\FieldNormalizer;
use JohannSchopplich\KirbyTools\FieldResolver;
use JohannSchopplich\KirbyTools\ModelResolver;
use Kirby\Cms\App;
use Kirby\Cms\File;
use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Data\Data;
use Kirby\Exception\PermissionException;
use Kirby\Toolkit\A;

/**
 * Flattens a model's content into Lingohub's key–value format and merges
 * translations back.
 *
 * A key joins the names along a value's path with `_`: field names, structure
 * row indexes, and block IDs and types, e.g. `blocks_{id}_{type}_text`. Keys are
 * never split again – both directions walk the content and build the same keys,
 * so names that contain `_` stay intact.
 */
final class Content
{
    private Site|Page|File $model;

    public function __construct(string $modelId)
    {
        $this->model = ModelResolver::resolveFromId($modelId);
    }

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
     * and writes it back into the model content.
     */
    public function downloadTranslation(string $languageCode, array $options = []): void
    {
        // The update below impersonates Kirby to pass the `translateExternalOnly` guard,
        // so the user's own permission has to be checked first.
        if (!$this->model->permissions()->can('update')) {
            throw new PermissionException('You are not allowed to update this content');
        }

        $lingohub = Lingohub::instance();
        $filename = Lingohub::resolveResourceFilename($this->model, $languageCode);

        $serializedContent = $lingohub->downloadResource(
            $this->model->id(),
            $filename,
            $options
        );

        App::instance()->impersonate('kirby', function () use ($languageCode, $serializedContent) {
            $mergedContent = $this->deserializeContent($serializedContent, $languageCode);

            $this->model = $this->model->update($mergedContent, $languageCode);

            if (($serializedContent['title'] ?? '') !== '' && method_exists($this->model, 'changeTitle')) {
                $this->model = $this->model->changeTitle($serializedContent['title'], $languageCode);
            }
        });
    }

    /**
     * Flattens the model's content into a key-value map for Lingohub.
     */
    public function serializeContent(string $languageCode): array
    {
        $segments = [];

        $this->walk(
            $this->model->content($languageCode)->toArray(),
            $this->resolveFields(),
            function (string $key, mixed $value, bool $isKirbyOnly, bool $isHidden) use (&$segments) {
                if (!$isKirbyOnly && !$isHidden) {
                    $segments[$key] = $value;
                }

                return $value;
            }
        );

        // `FieldResolver::resolveModelFields()` strips `title` and `slug`, so the title has to be added back by hand.
        // Read it from the language's content: `Page::title()` only knows the request language.
        $title = $this->model->content($languageCode)->get('title')->value();

        if (!$this->model instanceof File && $title !== null) {
            $segments['title'] = $title;
        }

        return $segments;
    }

    /**
     * Merges a Lingohub key-value map back into the model's content structure.
     *
     * A segment missing from the map or empty in it – e.g. one not approved
     * yet – keeps the target language's existing value, else the default
     * language's.
     */
    public function deserializeContent(array $serializedContent, string $languageCode): array
    {
        $defaultLanguageCode = App::instance()->defaultLanguage()->code();
        $targetContent = $this->model->content($languageCode)->toArray();
        $fields = $this->resolveFields();

        $targetValues = [];
        $this->walk(
            $targetContent,
            $fields,
            function (string $key, mixed $value) use (&$targetValues) {
                $targetValues[$key] = $value;
                return $value;
            }
        );

        // The default language content is the base: it carries the structure
        // Lingohub's keys address, such as block order and structure rows.
        $mergedContent = $this->walk(
            $this->model->content($defaultLanguageCode)->toArray(),
            $fields,
            function (string $key, mixed $value, bool $isKirbyOnly) use ($serializedContent, $targetValues) {
                // Lingohub may send an untranslated segment as an empty string.
                if (!$isKirbyOnly && ($serializedContent[$key] ?? '') !== '') {
                    return $serializedContent[$key];
                }

                return array_key_exists($key, $targetValues) ? $targetValues[$key] : $value;
            }
        );

        // The base carries the default title and slug. A downloaded title is
        // written through `changeTitle()` in `downloadTranslation()` instead.
        foreach (['title', 'slug'] as $name) {
            if (isset($targetContent[$name])) {
                $mergedContent[$name] = $targetContent[$name];
            }
        }

        return $mergedContent;
    }

    /**
     * Walks the content along the paths Lingohub keys address and passes every
     * text-like segment, and every `translateInKirbyOnly` field as a whole, to
     * `$visit`, whose return value replaces the visited value.
     *
     * @param \Closure(string $key, mixed $value, bool $isKirbyOnly, bool $isHidden): mixed $visit
     */
    private function walk(array $content, array $fields, Closure $visit, string $prefix = '', bool $isHidden = false): array
    {
        foreach ($content as $name => $value) {
            $field = $fields[$name] ?? null;

            if ($field === null || !($field['translate'] ?? true)) {
                continue;
            }

            $key = $prefix === '' ? (string)$name : "{$prefix}_{$name}";

            if ($field['translateInKirbyOnly'] ?? false) {
                $content[$name] = $visit($key, $value, true, $isHidden);
            } elseif ($this->isTextLikeField($field)) {
                $content[$name] = $visit($key, $value, false, $isHidden);
            } elseif (in_array($field['type'] ?? null, ['blocks', 'layout', 'structure', 'object'], true)) {
                $content[$name] = $this->walkNestedField($field, $value, $visit, $key, $isHidden);
            }
        }

        return $content;
    }

    private function walkNestedField(array $field, mixed $value, Closure $visit, string $key, bool $isHidden): mixed
    {
        $format = in_array($field['type'], ['blocks', 'layout'], true) ? 'json' : 'yaml';
        $data = is_string($value) ? Data::decode($value, $format) : $value;

        if (!is_array($data)) {
            return $value;
        }

        $walkedData = match ($field['type']) {
            'blocks' => $this->walkBlocks($data, $field['fieldsets'] ?? [], $visit, $key, $isHidden),
            'layout' => $this->walkLayouts($data, $field['fieldsets'] ?? [], $visit, $key, $isHidden),
            'structure' => $this->walkStructure($data, $field['fields'] ?? [], $visit, $key, $isHidden),
            'object' => A::isAssociative($data)
                ? $this->walk($data, $field['fields'] ?? [], $visit, $key, $isHidden)
                : $data
        };

        // Keep the stored representation of a value nothing was replaced in.
        if ($walkedData === $data) {
            return $value;
        }

        return is_string($value) ? Data::encode($walkedData, $format) : $walkedData;
    }

    private function walkStructure(array $rows, array $fields, Closure $visit, string $key, bool $isHidden): array
    {
        foreach ($rows as $index => $row) {
            if (is_array($row)) {
                $rows[$index] = $this->walk($row, $fields, $visit, "{$key}_{$index}", $isHidden);
            }
        }

        return $rows;
    }

    private function walkBlocks(array $blocks, array $fieldsets, Closure $visit, string $key, bool $isHidden): array
    {
        foreach ($blocks as $index => $block) {
            if (
                !isset($block['id'], $block['type'], $fieldsets[$block['type']]) ||
                !is_array($block['content'] ?? null) ||
                !A::isAssociative($block['content'])
            ) {
                continue;
            }

            $blocks[$index]['content'] = $this->walk(
                $block['content'],
                $this->flattenTabFields($fieldsets[$block['type']]),
                $visit,
                "{$key}_{$block['id']}_{$block['type']}",
                $isHidden || ($block['isHidden'] ?? false) === true
            );
        }

        return $blocks;
    }

    /**
     * Layout blocks are keyed like the blocks of a blocks field – layout and
     * column IDs are not part of the key.
     */
    private function walkLayouts(array $layouts, array $fieldsets, Closure $visit, string $key, bool $isHidden): array
    {
        foreach ($layouts as $layoutIndex => $layout) {
            foreach ($layout['columns'] ?? [] as $columnIndex => $column) {
                $layouts[$layoutIndex]['columns'][$columnIndex]['blocks'] = $this->walkBlocks(
                    $column['blocks'] ?? [],
                    $fieldsets,
                    $visit,
                    $key,
                    $isHidden
                );
            }
        }

        return $layouts;
    }

    private function resolveFields(): array
    {
        return FieldNormalizer::normalizeFields(FieldResolver::resolveModelFields($this->model));
    }

    private function isTextLikeField(array $field): bool
    {
        // Custom types are already resolved to their base type by `FieldNormalizer::normalizeFields()`.
        static $textLikeTypes = ['list', 'tags', 'text', 'textarea', 'writer', 'markdown'];
        return in_array($field['type'] ?? '', $textLikeTypes, true);
    }

    private function flattenTabFields(array $fieldset): array
    {
        $fields = [];

        foreach ($fieldset['tabs'] ?? [] as $tab) {
            $fields = array_merge($fields, $tab['fields'] ?? []);
        }

        return $fields;
    }
}
