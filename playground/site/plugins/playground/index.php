<?php

use Kirby\Cms\App;
use Kirby\Panel\Panel;

App::plugin('johannschopplich/playground', [
    'areas' => [
        'login' => fn (App $kirby) => [
            'views' => [
                'login' => [
                    'pattern' => 'login',
                    'auth' => false,
                    'action' => function () use ($kirby) {
                        if ($kirby->user() === null) {
                            $role = $kirby->request()->get('role') ?? 'admin';
                            $kirby->users()->role($role)->first()->loginPasswordless([
                                'long' => true
                            ]);
                        }

                        Panel::go('site');
                    }
                ]
            ]
        ]
    ],
    'hooks' => [
        // Kirby drops the login view while someone is signed in, so
        // `login?role=…` first signs out a user of another role.
        'route:before' => function (string $path) {
            $kirby = App::instance();
            $role = $kirby->request()->get('role');

            if ($role === null || $path !== $kirby->option('panel.slug', 'panel') . '/login') {
                return;
            }

            $user = $kirby->user();

            if ($user !== null && $user->role()->id() !== $role) {
                $user->logout();
            }
        }
    ]
]);
