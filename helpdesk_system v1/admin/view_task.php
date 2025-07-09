<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Administrator') {
    header("Location: ../index.php");
    exit;
}

$task_id = $_GET['id'] ?? null;
if (!$task_id) die("❌ Task ID missing.");

// 🔎 Fetch task
$stmt = $conn->prepare("
    SELECT t.*, d.name AS department_name,
           u.name AS raised_by, u.user_id AS raised_by_id,
           s.name AS staff_name
    FROM tasks t
    LEFT JOIN departments d ON t.department_id = d.id
    LEFT JOIN users u ON t.user_id = u.user_id
    LEFT JOIN users s ON t.support_staff_id = s.user_id
    WHERE t.id = ?
");
$stmt->bind_param("i", $task_id);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();
if (!$task) die("❌ Task not found.");

// 💬 Handle admin comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $comment = trim($_POST['comment']);
    $admin_id = $_SESSION['user']['user_id'];
    if (!empty($comment)) {
        $cstmt = $conn->prepare("INSERT INTO comments (task_id, user_id, comment) VALUES (?, ?, ?)");
        $cstmt->bind_param("iss", $task_id, $admin_id, $comment);
        $cstmt->execute();
        header("Location: view_task.php?id=$task_id");
        exit;
    }
}

// 🧾 Fetch comments
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

// 📜 Fetch logs
$logs = $conn->prepare("
    SELECT * FROM task_logs
    WHERE task_id = ?
    ORDER BY created_at DESC
");
$logs->bind_param("i", $task_id);
$logs->execute();
$log_result = $logs->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin View Task</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .box {
            max-width: 800px;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px #ccc;
        }
        .comment, .log-entry {
            background: #f9f9f9;
            padding: 10px;
            margin-bottom: 10px;
            border-left: 4px solid #0d6efd;
        }
        .meta {
            font-size: 13px;
            color: #555;
            margin-bottom: 5px;
        }
        textarea {
            width: 100%;
            height: 80px;
        }
        img {
            max-width: 300px;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div style="text-align: center;">
    <img src="../assets/logo.jpeg" alt="USI Logo" style="height:100px;">
    <h1>United Service Institution of India</h1>
</div>


<div class="box">
    <h2>📌 <?= htmlspecialchars($task['title']) ?> (<?= $task['reference_no'] ?>)</h2>
    <p><strong>Department:</strong> <?= htmlspecialchars($task['department_name']) ?></p>
    <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($task['description'])) ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars($task['status']) ?></p>
    <p><strong>Raised By:</strong> <?= htmlspecialchars($task['raised_by']) ?> (<?= $task['raised_by_id'] ?>)</p>
    <p><strong>Designation:</strong> <?= $task['designation'] ?></p>
    <p><strong>Assigned Staff:</strong> <?= htmlspecialchars($task['staff_name'] ?? 'Unassigned') ?></p>
    <p><strong>Created:</strong> <?= $task['created_at'] ?></p>

    <?php if (!empty($task['file_path'])): ?>
        <p><strong>Attachment:</strong><br>
            <?php
            $ext = pathinfo($task['file_path'], PATHINFO_EXTENSION);
            if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png'])): ?>
                <img src="../<?= $task['file_path'] ?>" alt="Attachment"><br>
            <?php endif; ?>
            <a href="../<?= $task['file_path'] ?>" target="_blank">📥 Download <?= strtoupper($ext) ?></a>
        </p>
    <?php else: ?>
        <p><strong>Attachment:</strong> None</p>
    <?php endif; ?>
</div>

<div class="box">
    <h3>💬 Comments</h3>
    <?php if ($comment_result->num_rows === 0): ?>
        <p>No comments yet.</p>
    <?php else: ?>
        <?php while ($c = $comment_result->fetch_assoc()): ?>
            <div class="comment">
                <div class="meta"><?= htmlspecialchars($c['name']) ?> (<?= $c['role'] ?>) | <?= $c['created_at'] ?></div>
                <div><?= nl2br(htmlspecialchars($c['comment'])) ?></div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>

    <hr>
    <h3>➕ Add Admin Comment</h3>
    <form method="POST">
        <textarea name="comment" required></textarea><br>
        <button type="submit">Submit</button>
    </form>
</div>

<div class="box">
    <h3>📜 Activity Logs</h3>
    <?php if ($log_result->num_rows === 0): ?>
        <p>No actions logged yet.</p>
    <?php else: ?>
        <?php while ($log = $log_result->fetch_assoc()): ?>
            <div class="log-entry">
                <div class="meta">
                    <?= htmlspecialchars($log['created_at']) ?> |
                    <?= htmlspecialchars($log['role']) ?> (<?= $log['actor_user_id'] ?>)
                </div>
                <div><?= htmlspecialchars($log['action']) ?></div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>

<br><div style="text-align:center;">
    <a href="dashboard.php">⬅ Back to Admin Dashboard</a>
</div>

</body>
</html>
