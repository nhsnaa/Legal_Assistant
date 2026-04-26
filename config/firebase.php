<?php

return [
    'default' => 'main',

    'projects' => [
        'main' => [
            'credentials' => [
                'file' => [
                    'project_id'   => env('FIREBASE_PROJECT_ID'),
                    'client_email' => env('FIREBASE_CLIENT_EMAIL'),
                    'private_key'  => str_replace('\n', "\n", env('FIREBASE_PRIVATE_KEY', '')),
                ],
            ],
        ],
    ],
];