<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'User') {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user']['user_id'];
$task_id = $_GET['id'] ?? null;

if (!$task_id) {
    die("❌ Task ID missing.");
}

// ✅ Fetch task belonging to this user
$stmt = $conn->prepare("
    SELECT t.*, d.name AS department_name, s.name AS support_staff
    FROM tasks t
    LEFT JOIN departments d ON t.department_id = d.id
    LEFT JOIN users s ON t.support_staff_id = s.user_id
    WHERE t.id = ? AND t.user_id = ?
");
$stmt->bind_param("is", $task_id, $user_id);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();

if (!$task) {
    die("❌ Task not found or access denied.");
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

// ✅ Handle comment post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $comment = trim($_POST['comment']);
    $admin_id = $_SESSION['user']['user_id'];
    $uploaded_paths = [];

    if (!empty($_FILES['attachments']['name'][0])) {
        $upload_dir = "../uploads/";
        $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        // 🔒 Limit to 10 files
        foreach ($_FILES['attachments']['name'] as $key => $filename) {
            if ($key >= 10) break; // 🔒 Limit to 10 files

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

    $file_paths_str = implode(',', $uploaded_paths); // Save as CSV string

    $stmt = $conn->prepare("INSERT INTO comments (task_id, user_id, comment, attachment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $task_id, $admin_id, $comment, $file_paths_str);
    $stmt->execute();

    header("Location: view_task.php?id=$task_id");
    exit;
}
// ✅ Fetch task logs
$logs = $conn->prepare("
    SELECT l.*, u.name, u.role
    FROM task_logs l
    JOIN users u ON l.actor_user_id = u.user_id
    WHERE l.task_id = ?
    ORDER BY l.created_at ASC
");


// ✅ Decode attachment list
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
            max-width: 800px;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px #ccc;
        }
        .attachment {
            margin: 10px 0;
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
        }
    </style>
</head>
<body>

<div style="text-align: center;">
    <img src="../assets/logo.jpeg" alt="USI Logo" style="height:100px;">
    <h1>United Service Institution of India</h1>
</div>


<div class="box">
    <h2>📝 <?= htmlspecialchars($task['title']) ?> (<?= $task['reference_no'] ?>)</h2>
    <p><strong>Department:</strong> <?= htmlspecialchars($task['department_name']) ?></p>
    <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($task['description'])) ?></p>
    <p><strong>Status:</strong> <?= $task['status'] ?></p>
    <p><strong>Raised By:</strong> <?= htmlspecialchars($task['raised_by_name']) ?></p>
    <p><strong>Designation:</strong> <?= htmlspecialchars($task['designation']) ?></p>
    <p><strong>Support Staff:</strong> <?= htmlspecialchars($task['support_staff'] ?? 'Unassigned') ?></p>
    <p><strong>Created:</strong> <?= $task['created_at'] ?></p>

    <h4>📎 Attachments</h4>
    <?php if (count($attachments) === 0): ?>
        <p>None</p>
    <?php else: ?>
        <?php foreach ($attachments as $file): ?>
            <div class="attachment">
                <?php
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $is_img = in_array($ext, ['jpg', 'jpeg', 'png']);
                if ($is_img): ?>
                    <img src="../<?= $file ?>" alt="Attachment">
                <?php endif; ?>
                <a href="../<?= $file ?>" target="_blank">📥 <?= basename($file) ?></a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="box">
    <h3>💬 Comments</h3>
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

    <form method="POST" enctype="multipart/form-data">
    <textarea name="comment" required></textarea><br><br>
    <label>📎 Attach files (max 10):</label><br>
    <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf" /><br><br>
    <button type="submit">Submit</button>
</form>



    <br><a href="dashboard.php">⬅ Back to Dashboard</a>
</div>

</body>
</html>
