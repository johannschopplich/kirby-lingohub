<?php

use JohannSchopplich\Lingohub\Content;
use JohannSchopplich\Lingohub\Lingohub;
use JohannSchopplich\Lingohub\PanelContext;
use Kirby\Cms\App;

return [
    'routes' => fn (App $kirby) => [
        [
            'pattern' => '__lingohub__/context',
            'method' => 'GET',
            'action' => fn () => [
                'config' => PanelContext::config()
            ]
        ],
        [
            'pattern' => '__lingohub__/model-context',
            'method' => 'POST',
            'action' => function () use ($kirby) {
                $content = Content::fromPath($kirby->request()->body()->get('path', ''));

                $editorUrls = [];

                foreach ($kirby->languages() as $language) {
                    $editorUrls[$language->code()] = Lingohub::editorUrl($language->code());
                }

                return [
                    'resourceFiles' => $content->resourceFiles(),
                    'translatedLanguageCodes' => $content->translatedLanguageCodes(),
                    'editorUrls' => $editorUrls
                ];
            }
        ],
        [
            'pattern' => '__lingohub__/status',
            'method' => 'GET',
            'action' => fn () => [
                'translationStatus' => Lingohub::instance()->getTranslationStatus()
            ]
        ],
        [
            'pattern' => '__lingohub__/export',
            'method' => 'POST',
            'action' => function () use ($kirby) {
                $body = $kirby->request()->body();

                Content::fromPath($body->get('path', ''))->uploadTranslation(
                    $body->get('languageCode', $kirby->defaultLanguage()->code())
                );

                return ['status' => 'ok'];
            }
        ],
        [
            'pattern' => '__lingohub__/import',
            'method' => 'POST',
            'action' => function () use ($kirby) {
                $body = $kirby->request()->body();

                Content::fromPath($body->get('path', ''))->downloadTranslation(
                    $body->get('languageCode', $kirby->defaultLanguage()->code()),
                    ['target:filterByStatus' => $body->get('targetStatus', 'APPROVED')]
                );

                return ['status' => 'ok'];
            }
        ]
    ]
];
