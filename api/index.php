<?php

// Create required folders in the only writable space on Vercel
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

// Overwrite the environment variables for this request
putenv('VIEW_COMPILED_PATH=/tmp/storage/framework/views');
putenv('APP_STORAGE=/tmp/storage');

require __DIR__ . '/../public/index.php';