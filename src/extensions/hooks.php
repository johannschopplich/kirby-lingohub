<?php

use Kirby\Cms\Page;
use Kirby\Exception\PermissionException;

return [
    'page.update:before' => function (Page $page, array $values, array $strings) {
        /** @var \Kirby\Cms\App $this */
        if (!$this->multilang()) {
            return;
        }
        if ($this->currentLanguage()?->code() === $this->defaultLanguage()?->code()) {
            return;
        }
        if ($this->user()->role()->isAdmin()) {
            return;
        }

        $fields = $page->blueprint()->fields();

        foreach ($fields as $field) {
            if (!isset($field['translateExternalOnly']) || !$field['translateExternalOnly']) {
                continue;
            }

            if ($page->content()->get($field['name'])->value() === $strings[$field['name']]) {
                continue;
            }

            throw new PermissionException('Field "' . $field['label'] . '" is only allowed to be translated externally.');
        }
    }
];
