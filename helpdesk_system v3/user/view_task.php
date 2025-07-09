<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'User') {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user']['user_id'];
$task_id = $_GET['id'] ?? null;

if (!$task_id) die("❌ Task ID missing.");

// ✅ Fetch the task
$stmt = $conn->prepare("
    SELECT t.*, d.name AS department_name, s.name AS support_staff, u.name AS raised_by_name
    FROM tasks t
    LEFT JOIN departments d ON t.department_id = d.id
    LEFT JOIN users s ON t.support_staff_id = s.user_id
    LEFT JOIN users u ON t.user_id = u.user_id
    WHERE t.id = ? AND t.user_id = ?
");
$stmt->bind_param("ii", $task_id, $user_id);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();
if (!$task) die("❌ Task not found or access denied.");

// ✅ Check for linked task
$linked_task_ref = null;
if (!empty($task['linked_task_id'])) {
    $linked_id = $task['linked_task_id'];
    $linked_stmt = $conn->prepare("SELECT id, reference_no FROM tasks WHERE id = ?");
    $linked_stmt->bind_param("i", $linked_id);
    $linked_stmt->execute();
    $linked_data = $linked_stmt->get_result()->fetch_assoc();
    if ($linked_data) {
        $linked_task_ref = $linked_data;
    }
}

// ✅ Handle new comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $comment = trim($_POST['comment']);
    $uploaded_paths = [];

    if (!empty($_FILES['attachments']['name'][0])) {
        $upload_dir = "../uploads/";
        $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        foreach ($_FILES['attachments']['name'] as $key => $filename) {
            if ($key >= 10) break;
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_ext)) {
                $new_name = "comment_" . time() . "_" . uniqid() . "_" . basename($filename);
                $target_file = $upload_dir . $new_name;
                if (move_uploaded_file($_FILES['attachments']['tmp_name'][$key], $target_file)) {
                    $uploaded_paths[] = "uploads/" . $new_name;
                }
            }
        }
    }

    $file_paths_str = implode(',', $uploaded_paths);
    $stmt = $conn->prepare("INSERT INTO comments (task_id, user_id, comment, attachment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $task_id, $user_id, $comment, $file_paths_str);
    $stmt->execute();

    header("Location: view_task.php?id=$task_id");
    exit;
}

// ✅ Fetch comments
$comments = $conn->prepare("
    SELECT c.*, u.name, u.role
    FROM comments c
    JOIN users u ON c.user_id = u.user_id
    WHERE c.task_id = ?
    ORDER BY c.created_at ASC
");
$comments->bind_param("i", $task_id);
$comments->execute();
$comment_result = $comments->get_result();

// ✅ Fetch task logs
$logs = $conn->prepare("
    SELECT l.*, u.name, u.role
    FROM task_logs l
    JOIN users u ON l.actor_user_id = u.user_id
    WHERE l.task_id = ?
    ORDER BY l.created_at ASC
");
$logs->bind_param("i", $task_id);
$logs->execute();
$log_result = $logs->get_result();

// ✅ Decode file attachments
$attachments = [];
if (!empty($task['file_path'])) {
    $attachments = json_decode($task['file_path'], true) ?? [];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Task</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .box {
            max-width: 850px;
            margin: 20px auto;
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 10px #ccc;
        }
        .attachment img {
            max-width: 200px;
            display: block;
            margin-top: 5px;
        }
        .comment {
            background: #f1f1f1;
            padding: 10px;
            margin: 10px 0;
            border-left: 4px solid #0d6efd;
            border-radius: 5px;
        }
        textarea {
            width: 100%;
            min-height: 80px;
            padding: 10px;
        }
        table { width: 100%; margin-top: 10px; border-collapse: collapse; }
        th, td { padding: 8px; border: 1px solid #ccc; }
        th { background: royalblue; color: white; }
    </style>
</head>
<body>

<div style="text-align: center;">
    <img src="../assets/logo.jpeg" alt="USI Logo" style="height:100px;">
    <h1>United Service Institution of India</h1>
</div>

<div class="box">
    <h2>📝 <?= htmlspecialchars($task['title']) ?> (<?= $task['reference_no'] ?>)</h2>

    <?php if ($linked_task_ref): ?>
        <p style="background:#eef; padding:8px; border-left:4px solid #0d6efd;">
            🔗 Linked with:
            <strong><a href="view_task.php?id=<?= $linked_task_ref['id'] ?>" style="color:#0d6efd;">
                <?= htmlspecialchars($linked_task_ref['reference_no']) ?>
            </a></strong>
        </p>
    <?php endif; ?>

    <p><strong>Department:</strong> <?= htmlspecialchars($task['department_name']) ?></p>
    <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($task['description'])) ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars($task['status']) ?></p>
    <p><strong>Raised By:</strong> <?= htmlspecialchars($task['raised_by_name']) ?></p>
    <p><strong>Designation:</strong> <?= htmlspecialchars($task['designation']) ?></p>
    <p><strong>Support Staff:</strong> <?= htmlspecialchars($task['support_staff'] ?? 'Unassigned') ?></p>
    <p><strong>Created:</strong> <?= htmlspecialchars($task['created_at']) ?></p>

    <h4>📎 Attachments</h4
