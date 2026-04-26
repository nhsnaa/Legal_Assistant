<?php

return [
    'default' => 'main',

    'projects' => [
        'main' => [
            'credentials' => [
                'file' => env('FIREBASE_CREDENTIALS'),
            ],
        ],
    ],
];