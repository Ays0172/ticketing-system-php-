<?php
$filename = basename($_GET['file'] ?? '');
$filepath = __DIR__ . "/uploads/" . $filename;

if (!preg_match('/^file_[a-zA-Z0-9]+\.(jpg|jpeg|png|pdf)$/', $filename)) {
    die("⛔ Invalid filename.");
}

if (file_exists($filepath)) {
    unlink($filepath);
    echo "✅ File deleted.";
    // Also clear DB entry if applicable
} else {
    echo "🚫 File not found.";
}
