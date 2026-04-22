<?php
$dir = __DIR__ . '/../storage';
if (!is_dir($dir)) {
    if (mkdir($dir, 0777, true)) {
        echo "Directory created\n";
    } else {
        echo "Failed to create directory\n";
        exit;
    }
}
$file = $dir . '/test.txt';
if (file_put_contents($file, 'test')) {
    echo "Write successful\n";
    unlink($file);
} else {
    echo "Write failed\n";
}
