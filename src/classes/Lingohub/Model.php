<?php

declare(strict_types = 1);

namespace JohannSchopplich\Lingohub;

use Kirby\Cms\App;
use Kirby\Cms\File;
use Kirby\Cms\ModelWithContent;
use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Form\Form;

final class Model
{
    public static function resolveModelFields(ModelWithContent $model): array
    {
        $fields = $model->blueprint()->fields();
        $languageCode = $model->kirby()->languageCode();
        $content = $model->content($languageCode)->toArray();
        $form = new Form([
            'fields' => $fields,
            'values' => $content,
            'model' => $model,
            'strict' => true
        ]);

        $fields = $form->fields()->toArray();
        unset($fields['title']);

        foreach ($fields as $index => $props) {
            unset($fields[$index]['value']);
        }

        return $fields;
    }

    public static function resolveModel(string $modelId): Site|Page|File
    {
        $kirby = App::instance();

        return $modelId === 'site'
            ? $kirby->site()
            : $kirby->page($modelId, drafts: true) ?? $kirby->file($modelId, drafts: true);
    }
}
