<?php

// 1. Force environment to errorlog to stop the "Read-only" crash
putenv('LOG_CHANNEL=errorlog');
putenv('APP_DEBUG=true');

// 2. Locate and KILL the cache files
$cachePath = __DIR__ . '/../bootstrap/cache';
$files = ['config.php', 'services.php', 'packages.php', 'routes.php'];

foreach ($files as $file) {
    if (file_exists("$cachePath/$file")) {
        @unlink("$cachePath/$file");
    }
}

// 3. Create the /tmp directories for Blade (the View engine)
$storagePath = '/tmp/storage/framework/views';
if (!is_dir($storagePath)) {
    mkdir($storagePath, 0777, true);
}

// 4. Now load the application
require __DIR__ . '/../public/index.php';