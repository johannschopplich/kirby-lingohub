<?php

declare(strict_types = 1);

namespace JohannSchopplich\Lingohub;

use Kirby\Cms\App;
use Kirby\Cms\File;
use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Exception\PermissionException;
use Kirby\Toolkit\I18n;

final class TranslateExternalOnly
{
    /**
     * Rejects an update that changes a top-level `translateExternalOnly` field in
     * a secondary language. Admins are exempt, and so is a download from
     * Lingohub, which runs as the almighty Kirby user.
     *
     * @param array<string, mixed> $strings The values the update stores
     * @throws \Kirby\Exception\PermissionException
     */
    public static function guard(Site|Page|File $model, array $strings, string|null $languageCode): void
    {
        $kirby = App::instance();

        if (!$kirby->multilang() || $kirby->user()?->isAdmin()) {
            return;
        }

        $languageCode ??= $kirby->currentLanguage()?->code();

        if ($languageCode === $kirby->defaultLanguage()?->code()) {
            return;
        }

        $content = $model->content($languageCode);

        foreach ($model->blueprint()->fields() as $field) {
            $name = $field['name'];
            // Kirby's form stores the field names in lowercase.
            $key = strtolower($name);

            if (!($field['translateExternalOnly'] ?? false) || !array_key_exists($key, $strings)) {
                continue;
            }

            if ((string)$content->get($name)->value() === (string)$strings[$key]) {
                continue;
            }

            $label = $field['label'] ?? null;

            throw new PermissionException(I18n::template(
                'johannschopplich.lingohub.error.translateExternalOnly',
                null,
                ['label' => I18n::translate($label, $label) ?? $name]
            ));
        }
    }
}
