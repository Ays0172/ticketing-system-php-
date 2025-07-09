<?php
session_start();
include '../db.php';

// ✅ Corrected role check to match login role string
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Support Staff') {
    header("Location: ../index.php");
    exit;
}

$task_id = $_GET['id'] ?? null;
if (!$task_id) die("❌ Task ID missing.");

// ✅ Fixed session variable for user ID
$user_id = $_SESSION['user']['user_id'];


// ✅ FIXED: Corrected bind_param (only one call)
$stmt = $conn->prepare("
    SELECT t.*, d.name AS department_name, 
           u.name AS raised_by, s.name AS support_staff
    FROM tasks t
    LEFT JOIN departments d ON t.department_id = d.id
    LEFT JOIN users u ON t.user_id = u.user_id
    LEFT JOIN users s ON t.support_staff_id = s.user_id
    WHERE t.id = ? AND t.support_staff_id = ?
");

$stmt->bind_param("ii", $task_id, $user_id);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();
if (!$task) die("❌ Task not found.");

// Comment submission
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

// Comments
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

// Logs
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

$attachments = [];
if (!empty($task['file_path'])) {
    $attachments = json_decode($task['file_path'], true) ?? [];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Support - View Task</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .box { max-width: 900px; margin: 30px auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 0 10px #ccc; }
        .attachment img { max-width: 250px; margin-top: 10px; }
        .comment, .log-entry { background: #f1f1f1; padding: 10px; margin: 10px 0; border-left: 4px solid #0d6efd; border-radius: 5px; }
        textarea { width: 100%; min-height: 80px; padding: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ccc; }
        th { background-color: royalblue; color: white; }
        .status-pending { background: gold; padding: 5px 10px; border-radius: 5px; font-weight: bold; }
        .status-opened { background: crimson; color: white; padding: 5px 10px; border-radius: 5px; font-weight: bold; }
        .status-resolved { background: forestgreen; color: white; padding: 5px 10px; border-radius: 5px; font-weight: bold; }
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
    <p><strong>Status:</strong>
        <?php if ($_SESSION['user']['role'] === 'Support'): ?>
            <a href="redirect_task.php?id=<?= $task_id ?>">🔄 Reassign Task</a>
        <?php endif; ?>
        <?php
            $status = $task['status'];
            if ($status === 'Pending') echo '<span class="status-pending">Pending</span>';
            elseif ($status === 'Opened') echo '<span class="status-opened">Opened</span>';
            elseif ($status === 'Resolved') echo '<span class="status-resolved">Resolved</span>';
            else echo htmlspecialchars($status);
        ?>
    </p>
    <p><strong>Raised By:</strong> <?= htmlspecialchars($task['raised_by']) ?></p>
    <p><strong>Designation:</strong> <?= htmlspecialchars($task['designation']) ?></p>
    <p><strong>Assigned Support Staff:</strong> <?= htmlspecialchars($task['support_staff'] ?? 'Unassigned') ?></p>
    <p><strong>Created:</strong> <?= htmlspecialchars($task['created_at']) ?></p>

    <h4>📎 Attachments</h4>
    <?php if (count($attachments) === 0): ?>
        <p>No attachments</p>
    <?php else: ?>
        <?php foreach ($attachments as $file): ?>
            <div class="attachment">
                <?php
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $safe = "../" . htmlspecialchars($file);
                if (in_array($ext, ['jpg', 'jpeg', 'png'])):
                ?>
                    <img src="<?= $safe ?>" alt="Attachment"><br>
                <?php elseif ($ext === 'pdf'): ?>
                    <embed src="<?= $safe ?>" type="application/pdf" width="100%" height="400px"><br>
                <?php endif; ?>
                📎 <a href="<?= $safe ?>" target="_blank">View</a> | <a href="<?= $safe ?>" download>Download</a>
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
                <?php if (!empty($c['attachment'])): ?>
                    <div>
                        <strong>📎 Attachments:</strong><br>
                        <?php foreach (explode(',', $c['attachment']) as $path): ?>
                            <?php
                                $safePath = htmlspecialchars($path);
                                $ext = strtolower(pathinfo($safePath, PATHINFO_EXTENSION));
                                $fullPath = "../" . $safePath;
                            ?>
                            <?php if (in_array($ext, ['jpg', 'jpeg', 'png'])): ?>
                                <img src="<?= $fullPath ?>" alt="Image"><br>
                            <?php elseif ($ext === 'pdf'): ?>
                                <embed src="<?= $fullPath ?>" type="application/pdf" width="100%" height="400px"><br>
                            <?php endif; ?>
                            📄 <a href="<?= $fullPath ?>" target="_blank">👁 View</a> |
                            <a href="<?= $fullPath ?>" download>⬇ <?= basename($safePath) ?></a><br><br>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <textarea name="comment" required placeholder="Enter your response here..."></textarea><br><br>
        <label>📎 Attach files (max 10):</label><br>
        <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" /><br><br>
        <button type="submit">Submit</button>
    </form>
    <br><a href="dashboard.php">⬅ Back to Dashboard</a>
</div>

<div class="box">
    <h3>📜 Task Log</h3>
    <?php if ($log_result->num_rows === 0): ?>
        <p>No actions logged yet.</p>
    <?php else: ?>
        <?php while ($log = $log_result->fetch_assoc()): ?>
            <div class="log-entry">
                <strong><?= htmlspecialchars($log['name']) ?> (<?= $log['role'] ?>)</strong> @ <?= $log['created_at'] ?><br>
                <?= htmlspecialchars($log['action']) ?>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>

</body>
</html>
