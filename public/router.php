<?php

// Router script for PHP built-in server
// This ensures all requests go through index.php

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve static files directly if they exist
if ($path !== '/' && file_exists(__DIR__ . $path)) {
    return false; // Let the built-in server serve the file
}

// Otherwise, route through index.php (only once per process)
require_once __DIR__ . '/index.php';
