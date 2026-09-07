<?php

return [
    'debug' => true,

    'languages' => true,

    'content' => [
        'locking' => false
    ],

    'panel' => [
        'css' => array_filter([
            env('DEMO') ? 'assets/panel.css' : null
        ]),
        'vue' => [
            'compiler' => false
        ]
    ],

    'johannschopplich.lingohub' => [
        'apiKey' => env('LINGOHUB_API_KEY'),
        'workspaceId' => env('LINGOHUB_WORKSPACE_ID'),
        'projectId' => env('LINGOHUB_PROJECT_ID')
    ]
];
