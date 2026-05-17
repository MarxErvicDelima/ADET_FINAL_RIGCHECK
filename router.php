<?php
// Simple router to bypass Herd auto-loader issues
if (php_sapi_name() == 'cli-server') {
    $url = parse_url($_SERVER["REQUEST_URI"]);
    $file = __DIR__ . $url["path"];
    
    if (is_file($file)) {
        return false;
    }
}

// Route to the requested file
$requested_file = basename($_SERVER["REQUEST_URI"], '?') . '.php';
if (file_exists(__DIR__ . '/' . $requested_file)) {
    include __DIR__ . '/' . $requested_file;
} else {
    echo "File not found";
}
?>
