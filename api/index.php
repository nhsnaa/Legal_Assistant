<?php

// 1. Create the directories
$storagePath = '/tmp/storage';
foreach (['/framework/views', '/framework/cache', '/framework/sessions', '/logs'] as $dir) {
    if (!is_dir($storagePath . $dir)) {
        mkdir($storagePath . $dir, 0755, true);
    }
}

// 2. Inject these into the PHP environment immediately
putenv("APP_STORAGE=$storagePath");
putenv("VIEW_COMPILED_PATH=$storagePath/framework/views");

// 3. Launch
require __DIR__ . '/../public/index.php';