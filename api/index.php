<?php

// 1. Create the directories in the only writable space
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

// 2. Set environment variables that Laravel will pick up during boot
$_ENV['VIEW_COMPILED_PATH'] = '/tmp/storage/framework/views';
$_ENV['APP_STORAGE'] = '/tmp/storage';

// 3. Launch the app
require __DIR__ . '/../public/index.php';