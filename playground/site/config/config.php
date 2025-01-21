<?php

return [
    'debug' => true,

    'languages' => true,

    'content' => [
        'locking' => false
    ],

    'panel' => [
        'css' => 'assets/panel.css'
    ],

    'johannschopplich.lingohub' => [
        'apiKey' => env('LINGOHUB_API_KEY'),
        'workspaceId' => env('LINGOHUB_WORKSPACE_ID'),
        'projectId' => env('LINGOHUB_PROJECT_ID')
    ]
];
