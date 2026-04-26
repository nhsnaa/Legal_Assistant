<?php

// Make folders
$paths = ['/tmp/storage/framework/views', '/tmp/storage/framework/sessions', '/tmp/storage/framework/cache'];
foreach ($paths as $path) {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

require __DIR__ . '/../public/index.php';