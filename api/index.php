<?php

// Force the log channel to 'errorlog' so it doesn't try to write to the disk
putenv('LOG_CHANNEL=errorlog');
// 1. Manually kill any cached config files brought over from your laptop
$cacheConfig = __DIR__ . '/../bootstrap/cache/config.php';
if (file_exists($cacheConfig)) {
    unlink($cacheConfig);
}

// 2. Setup the /tmp storage
$storagePath = '/tmp/storage';
foreach (['/framework/views', '/framework/cache', '/framework/sessions', '/logs'] as $dir) {
    if (!is_dir($storagePath . $dir)) {
        mkdir($storagePath . $dir, 0755, true);
    }
}

// 3. Force environment
putenv("APP_STORAGE=$storagePath");
putenv("VIEW_COMPILED_PATH=$storagePath/framework/views");

// 4. Launch
require __DIR__ . '/../public/index.php';