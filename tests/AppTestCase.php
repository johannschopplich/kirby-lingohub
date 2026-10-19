<?php

declare(strict_types = 1);

use Kirby\Cms\App;
use Kirby\Cms\Blueprint;
use Kirby\Filesystem\Dir;
use Kirby\Toolkit\Str;
use PHPUnit\Framework\TestCase;

abstract class AppTestCase extends TestCase
{
    protected function tearDown(): void
    {
        App::destroy();
        // Blueprints are cached across apps by name.
        Blueprint::$loaded = [];
        Dir::remove(static::indexRoot());
    }

    protected static function bootApp(array $props = []): App
    {
        // `App::destroy()` also drops the registered plugins.
        if (App::plugin('johannschopplich/lingohub') === null) {
            require dirname(__DIR__) . '/index.php';
        }

        return new App(array_replace_recursive([
            // Without an index root, Kirby creates its content, media and site folders inside `vendor/getkirby/`.
            'roots' => ['index' => static::indexRoot()]
        ], $props));
    }

    protected static function indexRoot(): string
    {
        return __DIR__ . '/tmp/' . Str::kebab((new ReflectionClass(static::class))->getShortName());
    }
}
