<?php

// 1. Create the directories in /tmp
$basePath = '/tmp/storage';
$paths = [
    $basePath . '/framework/views',
    $basePath . '/framework/cache',
    $basePath . '/framework/sessions',
    $basePath . '/logs',
];

foreach ($paths as $path) {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

// 2. Force environment variables for Laravel 11
$_ENV['APP_STORAGE'] = $basePath;
$_ENV['VIEW_COMPILED_PATH'] = $basePath . '/framework/views';
$_SERVER['APP_STORAGE'] = $basePath;
$_SERVER['VIEW_COMPILED_PATH'] = $basePath . '/framework/views';

// 3. Launch the app
require __DIR__ . '/../public/index.php';