<?php

use Kirby\Cms\App;

@include_once __DIR__ . '/vendor/autoload.php';

App::plugin('johannschopplich/lingohub', [
    'api' => require __DIR__ . '/src/extensions/api.php',
    'hooks' => require __DIR__ . '/src/extensions/hooks.php',
    'sections' => require __DIR__ . '/src/extensions/sections.php',
    'translations' => require __DIR__ . '/src/extensions/translations.php'
]);
