<?php

use Kirby\Cms\App;
use JohannSchopplich\Lingohub\Content;
use JohannSchopplich\Lingohub\Model;

return [
    'routes' => fn (App $kirby) => [
        [
            'pattern' => '__lingohub__/context',
            'method' => 'GET',
            'action' => function () use ($kirby) {
                $config = $kirby->option('johannschopplich.lingohub', []);
                $languages = $kirby->languages()->toArray();

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
                $model = Model::resolveModel($id);

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
