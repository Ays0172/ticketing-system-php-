<?php
$filename = basename($_GET['file'] ?? '');
$filepath = __DIR__ . "/uploads/" . $filename;

if (!preg_match('/^file_[a-zA-Z0-9]+\.(jpg|jpeg|png|pdf)$/', $filename)) {
    die("⛔ Invalid filename.");
}

if (!file_exists($filepath)) {
    die("🚫 File not found.");
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
header("Content-Type: " . finfo_file($finfo, $filepath));
header("Content-Disposition: inline; filename=\"$filename\"");
header("Content-Length: " . filesize($filepath));
readfile($filepath);
exit;
