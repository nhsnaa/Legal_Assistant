<?php

// 1. Fix for Vercel's Read-Only Filesystem
# This creates the necessary folders in /tmp so Laravel doesn't crash
$storagePaths = [
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/logs',
];

foreach ($storagePaths as $path) {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

// 2. Point Laravel to the temporary storage
# This overrides the default storage path globally
putenv('APP_STORAGE=/tmp/storage');
putenv('VIEW_COMPILED_PATH=/tmp/storage/framework/views');

// 3. Launch the App
require __DIR__ . '/../public/index.php';