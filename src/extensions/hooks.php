<?php

use JohannSchopplich\Lingohub\TranslateExternalOnly;
use Kirby\Cms\File;
use Kirby\Cms\Page;
use Kirby\Cms\Site;

return [
    'page.update:before' => function (Page $page, array $strings, string|null $languageCode = null) {
        TranslateExternalOnly::guard($page, $strings, $languageCode);
    },
    'site.update:before' => function (Site $site, array $strings, string|null $languageCode = null) {
        TranslateExternalOnly::guard($site, $strings, $languageCode);
    },
    'file.update:before' => function (File $file, array $strings, string|null $languageCode = null) {
        TranslateExternalOnly::guard($file, $strings, $languageCode);
    }
];
