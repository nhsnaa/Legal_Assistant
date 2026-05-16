<?php

return [
    'default' => 'main',

    'projects' => [
        'main' => [
            'credentials' => [
                'file' => [
                    'type'         => 'service_account',
                    'project_id'   => env('FIREBASE_PROJECT_ID'),
                    'client_email' => env('FIREBASE_CLIENT_EMAIL'),
                    'private_key'  => str_replace('\n', "\n", env('FIREBASE_PRIVATE_KEY', '')),
                ],
            ],
            'database' => [
                'url' => env('FIREBASE_DATABASE_URL'),
            ],
            'storage' => [
                'default_bucket' => env('FIREBASE_STORAGE_BUCKET'),
            ],
        ],
    ],
];
