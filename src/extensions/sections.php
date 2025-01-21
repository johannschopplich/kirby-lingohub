<?php

use Kirby\Toolkit\I18n;

return [
    'lingohub-status' => [
        'props' => [
            'label' => fn ($label = null) => I18n::translate($label, $label)
        ]
    ]
];
