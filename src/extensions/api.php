<?php

use JohannSchopplich\KirbyPlugins\ModelResolver;
use JohannSchopplich\Lingohub\Content;
use Kirby\Cms\App;

return [
    'routes' => fn (App $kirby) => [
        [
            'pattern' => '__lingohub__/context',
            'method' => 'GET',
            'action' => function () use ($kirby) {
                $config = $kirby->option('johannschopplich.lingohub', []);
                $languages = $kirby->languages()->toArray(fn ($language) => array_merge(
                    $language->toArray(),
                    // Resolve the `LC_ALL` locale value explicitly to ensure
                    // consistent JSON serialization across platforms (the `LC_*`
                    // constants have different integer values on macOS vs Linux,
                    // causing the locale array keys to differ in JSON output)
                    ['locale' => [$language->locale(LC_ALL) ?? $language->code()]]
                ));

                return [
                    'config' => $config,
                    'languages' => $languages
                ];
            }
        ],
        [
            'pattern' => '__lingohub__/model-context',
            'method' => 'POST',
            'action' => function () use ($kirby) {
                $id = $kirby->request()->body()->get('id');
                $model = ModelResolver::resolveFromId($id);

                $availableTranslationLanguageCodes = $model
                    ->translations()
                    ->filter(fn ($translation) => $translation->code() !== $kirby->defaultLanguage()->code() && $translation->exists())
                    ->pluck('code');

                return [
                    'availableTranslationLanguageCodes' => $availableTranslationLanguageCodes
                ];
            }
        ],
        [
            'pattern' => '__lingohub__/export',
            'method' => 'POST',
            'action' => function () use ($kirby) {
                $id = $kirby->request()->body()->get('id');
                $languageCode = $kirby->request()->body()->get('languageCode', $kirby->defaultLanguage()->code());

                $content = new Content($id);
                $responseContent = $content->uploadTranslation($languageCode);

                return [
                    'status' => 'ok',
                    'code' => 200,
                    'data' => $responseContent
                ];
            }
        ],
        [
            'pattern' => '__lingohub__/import',
            'method' => 'POST',
            'action' => function () use ($kirby) {
                $id = $kirby->request()->body()->get('id');
                $languageCode = $kirby->request()->body()->get('languageCode', $kirby->defaultLanguage()->code());
                $targetStatus = $kirby->request()->body()->get('targetStatus', 'APPROVED');

                $content = new Content($id);
                $responseContent = $content->downloadTranslation($languageCode, [
                    'target:filterByStatus' => $targetStatus
                ]);

                return [
                    'status' => 'ok',
                    'code' => 200,
                    'data' => $responseContent
                ];
            }
        ]
    ]
];
