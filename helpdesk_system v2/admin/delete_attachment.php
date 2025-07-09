<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Administrator') {
    die("Unauthorized");
}

$comment_id = $_POST['comment_id'] ?? null;
$attachment_index = $_POST['attachment_index'] ?? null;
$task_id = $_POST['task_id'] ?? null;

if (!$comment_id || $attachment_index === null || !$task_id) {
    die("Missing parameters");
}

$stmt = $conn->prepare("SELECT attachment FROM comments WHERE id = ?");
$stmt->bind_param("i", $comment_id);
$stmt->execute();
$res = $stmt->get_result();
$comment = $res->fetch_assoc();

if (!$comment) die("Comment not found");

$attachments = explode(',', $comment['attachment']);
$to_delete = $attachments[$attachment_index] ?? null;

if ($to_delete && file_exists("../$to_delete")) {
    unlink("../$to_delete");
}

unset($attachments[$attachment_index]);
$new_paths = implode(',', $attachments);

$update = $conn->prepare("UPDATE comments SET attachment = ? WHERE id = ?");
$update->bind_param("si", $new_paths, $comment_id);
$update->execute();

header("Location: view_task.php?id=$task_id");
exit;
?>
