<?php

// Create the directories
$storagePaths = [
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache',
    '/tmp/storage/framework/sessions',
];

foreach ($storagePaths as $path) {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

// Manually set the config overrides before the app starts
config(['view.compiled' => '/tmp/storage/framework/views']);
config(['session.files' => '/tmp/storage/framework/sessions']);

require __DIR__ . '/../public/index.php';