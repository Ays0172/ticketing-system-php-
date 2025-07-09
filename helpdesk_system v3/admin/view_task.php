<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Administrator') {
    header("Location: ../index.php");
    exit;
}

$task_id = $_GET['id'] ?? null;
if (!$task_id) die("❌ Task ID missing.");

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['link_task'])) {
    $ref_no = trim($_POST['link_ref_no']);

    $ref_stmt = $conn->prepare("SELECT id FROM tasks WHERE reference_no = ?");
    $ref_stmt->bind_param("s", $ref_no);
    $ref_stmt->execute();
    $ref_result = $ref_stmt->get_result()->fetch_assoc();

    if ($ref_result && $ref_result['id'] != $task_id) {
        $linked_id = $ref_result['id'];

        $check_stmt = $conn->prepare("SELECT id FROM task_links WHERE task_id = ? AND linked_task_id = ?");
        $check_stmt->bind_param("ii", $task_id, $linked_id);
        $check_stmt->execute();

        if ($check_stmt->get_result()->num_rows === 0) {
            $insert_link = $conn->prepare("INSERT INTO task_links (task_id, linked_task_id) VALUES (?, ?)");
            $insert_link->bind_param("ii", $task_id, $linked_id);
            $insert_link->execute();

            $actor_id = $_SESSION['user']['user_id'];
            $action = "Linked to task ref_no $ref_no";
            $log_stmt = $conn->prepare("INSERT INTO task_logs (task_id, actor_user_id, action) VALUES (?, ?, ?)");
            $log_stmt->bind_param("iis", $task_id, $actor_id, $action);
            $log_stmt->execute();
        }
    }

    header("Location: view_task.php?id=$task_id");
    exit;
}

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

$link_stmt = $conn->prepare("
    SELECT t.id, t.reference_no, t.title
    FROM task_links l
    JOIN tasks t ON t.id = l.linked_task_id
    WHERE l.task_id = ?
");
$link_stmt->bind_param("i", $task_id);
$link_stmt->execute();
$link_result = $link_stmt->get_result();

// NEW: get reverse links
$reverse_link_stmt = $conn->prepare("
    SELECT t.id, t.reference_no, t.title
    FROM task_links l
    JOIN tasks t ON t.id = l.task_id
    WHERE l.linked_task_id = ?
");
$reverse_link_stmt->bind_param("i", $task_id);
$reverse_link_stmt->execute();
$reverse_link_result = $reverse_link_stmt->get_result();
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
        embed {
            width: 100%;
            height: 400px;
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

    <?php if ($link_result->num_rows > 0 || $reverse_link_result->num_rows > 0): ?>
        <p><strong>🔗 Linked Tasks:</strong></p>
        <ul>
            <?php while ($lt = $link_result->fetch_assoc()): ?>
                <li>
                    ➡ <a href="view_task.php?id=<?= $lt['id'] ?>">
                        <?= htmlspecialchars($lt['reference_no']) ?> - <?= htmlspecialchars($lt['title']) ?>
                    </a>
                </li>
            <?php endwhile; ?>

            <?php while ($rt = $reverse_link_result->fetch_assoc()): ?>
                <li>
                    ⬅ <a href="view_task.php?id=<?= $rt['id'] ?>">
                        <?= htmlspecialchars($rt['reference_no']) ?> - <?= htmlspecialchars($rt['title']) ?>
                    </a>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php endif; ?>

    <?php if (!empty($task['file_path'])): ?>
        <?php
        $attachments = json_decode($task['file_path'], true);
        if (is_array($attachments)):
            echo "<ul>";
            foreach ($attachments as $path):
                $filename = basename($path);
                $ext = strtoupper(pathinfo($filename, PATHINFO_EXTENSION));
                echo "<li>📎 <a href='../$path' download>$filename ($ext)</a></li>";
            endforeach;
            echo "</ul>";
        else:
            echo "❌ Could not decode attachments.";
        endif;
        ?>
    <?php else: ?>
        <p>📎 No attachments uploaded.</p>
    <?php endif; ?>
</div>

<div class="box">
    <hr>
    <h3>🔗 Link Old Task</h3>
    <form method="POST">
        <label>Enter Reference No of Old Task:</label><br>
        <input type="text" name="link_ref_no" required>
        <button type="submit" name="link_task">Link Task</button>
    </form>

    <h3>💬 Comments</h3>
    <?php if ($comment_result->num_rows === 0): ?>
        <p>No comments yet.</p>
    <?php else: ?>
        <?php while ($c = $comment_result->fetch_assoc()): ?>
            <div class="comment">
                <div class="meta"><?= htmlspecialchars($c['name']) ?> (<?= $c['role'] ?>) | <?= $c['created_at'] ?></div>
                <div><?= nl2br(htmlspecialchars($c['comment'])) ?></div>

                <?php if (!empty($c['attachment'])): ?>
                    <div style="margin-top: 8px;">
                        <strong>📎 Attachments:</strong><br>
                        <?php foreach (explode(',', $c['attachment']) as $path): ?>
                            <?php if (!empty($path)): ?>
                                <?php
                                    $safePath = htmlspecialchars($path);
                                    $basename = basename($path);
                                    $ext = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
                                    $fullPath = "../" . $safePath;
                                ?>
                                <?php if (in_array($ext, ['jpg', 'jpeg', 'png'])): ?>
                                    <img src="<?= $fullPath ?>" alt="Image"><br>
                                <?php elseif ($ext === 'pdf'): ?>
                                    <embed src="<?= $fullPath ?>" type="application/pdf"><br>
                                <?php endif; ?>
                                📄 <a href="<?= $fullPath ?>" target="_blank">👁 View</a> |
                                <a href="<?= $fullPath ?>" download>⬇ <?= $basename ?></a> |
                                <a href="../delete_attachment.php?file=<?= urlencode($basename) ?>" onclick="return confirm('Delete this file?')">❌ Delete</a><br><br>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>

    <hr>
    <h3>➕ Add Admin Comment</h3>
    <form method="POST" enctype="multipart/form-data">
        <textarea name="comment" required></textarea><br><br>
        <label>📎 Attach files (max 10):</label><br>
        <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" /><br><br>
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
