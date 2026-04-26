<?php

// Force log to stderr so Vercel can catch it
ini_set('error_log', 'php://stderr');

$cachePath = __DIR__ . '/../bootstrap/cache';
@unlink("$cachePath/config.php");
@unlink("$cachePath/services.php");

require __DIR__ . '/../public/index.php';