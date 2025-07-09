<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Administrator') {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user']['user_id'];
$task_id = $_GET['id'] ?? null;

if (!$task_id) {
    die("❌ Task ID missing.");
}

// ✅ Fetch task
$stmt = $conn->prepare("
    SELECT t.*, d.name AS department_name, 
           u.name AS raised_by, s.name AS support_staff
    FROM tasks t
    LEFT JOIN departments d ON t.department_id = d.id
    LEFT JOIN users u ON t.user_id = u.user_id
    LEFT JOIN users s ON t.support_staff_id = s.user_id
    WHERE t.id = ?
");
$stmt->bind_param("i", $task_id);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();

if (!$task) {
    die("❌ Task not found.");
}

// ✅ Handle new comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $comment = trim($_POST['comment']);
    if (!empty($comment)) {
        $insert = $conn->prepare("INSERT INTO comments (task_id, user_id, comment) VALUES (?, ?, ?)");
        $insert->bind_param("iss", $task_id, $user_id, $comment);
        $insert->execute();
        header("Location: view_task.php?id=" . $task_id);
        exit;
    }
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

// ✅ Parse attachments
$attachments = [];
if (!empty($task['file_path'])) {
    $attachments = json_decode($task['file_path'], true) ?? [];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Task - Admin</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .box {
            max-width: 900px;
            margin: 30px auto;
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 10px #ccc;
        }
        .attachment img {
            max-width: 250px;
            margin-top: 10px;
        }
        .comment {
            background: #f1f1f1;
            padding: 10px;
            margin: 10px 0;
            border-left: 4px solid #0d6efd;
            border-radius: 5px;
        }
        .comment small {
            color: #666;
        }
        textarea {
            width: 100%;
            min-height: 80px;
            padding: 10px;
        }
    </style>
</head>
<body>

<div style="text-align: center;">
    <img src="../assets/logo.jpeg" alt="USI Logo" style="height:100px;">
    <h1>United Service Institution of India</h1>
</div>


<div class="box">
    <h2>📄 <?= htmlspecialchars($task['reference_no']) ?> — <?= htmlspecialchars($task['title']) ?></h2>
    <p><strong>Department:</strong> <?= htmlspecialchars($task['department_name']) ?></p>
    <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($task['description'])) ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars($task['status']) ?></p>
    <p><strong>Raised By:</strong> <?= htmlspecialchars($task['raised_by']) ?></p>
    <p><strong>Designation:</strong> <?= htmlspecialchars($task['designation']) ?></p>
    <p><strong>Assigned Support Staff:</strong> <?= htmlspecialchars($task['support_staff'] ?? 'Unassigned') ?></p>
    <p><strong>Created:</strong> <?= $task['created_at'] ?></p>

    <h4>📎 Attachments</h4>
    <?php if (count($attachments) === 0): ?>
        <p>No attachments</p>
    <?php else: ?>
        <?php foreach ($attachments as $file): ?>
            <div class="attachment">
                <?php
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png'])): ?>
                    <img src="../<?= $file ?>" alt="Attachment">
                <?php endif; ?>
                <br><a href="../<?= $file ?>" target="_blank">📥 Download <?= basename($file) ?></a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="box">
    <h3>💬 Conversation Log</h3>
    <?php if ($comment_result->num_rows === 0): ?>
        <p>No comments yet.</p>
    <?php else: ?>
        <?php while ($c = $comment_result->fetch_assoc()): ?>
            <div class="comment">
                <strong><?= htmlspecialchars($c['name']) ?> (<?= $c['role'] ?>)</strong><br>
                <?= nl2br(htmlspecialchars($c['comment'])) ?><br>
                <small>🕒 <?= $c['created_at'] ?></small>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>

    <form method="POST">
        <h4>Add a Comment</h4>
        <textarea name="comment" required></textarea><br>
        <button type="submit">Submit</button>
    </form>
    <br><a href="dashboard.php">⬅ Back to Dashboard</a>
</div>

</body>
</html>
