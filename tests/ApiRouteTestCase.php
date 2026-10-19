<?php

declare(strict_types = 1);

use Kirby\Cms\App;

abstract class ApiRouteTestCase extends AppTestCase
{
    protected function callRoute(App $kirby, string $pattern): mixed
    {
        $api = require dirname(__DIR__) . '/src/extensions/api.php';
        $routes = $api['routes']($kirby);

        foreach ($routes as $route) {
            if (($route['pattern'] ?? '') === $pattern) {
                return $route['action']();
            }
        }

        $this->fail("Route not found: {$pattern}");
    }
}
