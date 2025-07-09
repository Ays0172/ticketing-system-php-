<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Administrator') {
    header("Location: ../index.php");
    exit;
}

// Handle assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign'])) {
    $task_id = $_POST['task_id'];
    $staff_id = $_POST['staff_id'];

    $stmt = $conn->prepare("UPDATE tasks SET support_staff_id = ?, status = 'Opened' WHERE id = ?");
    $stmt->bind_param("si", $staff_id, $task_id);
    $stmt->execute();

    $admin_id = $_SESSION['user']['user_id'];
    $desc = "Assigned task to $staff_id";
    $role = 'Administrator';

    $log = $conn->prepare("INSERT INTO task_logs (task_id, action, actor_user_id, role) VALUES (?, ?, ?, ?)");
    $log->bind_param("isss", $task_id, $desc, $admin_id, $role);
    $log->execute();

    header("Location: dashboard.php");
    exit;
}

// Fetch support staff
$support_staff = $conn->query("SELECT user_id, name FROM users WHERE role = 'Support Staff'");

// Fetch tasks
$sql = "
    SELECT 
        t.id AS task_id,
        t.reference_no,
        t.title,
        t.status,
        t.created_at,
        t.file_path,
        t.support_staff_id,
        d.name AS department_name,
        u.name AS raised_by
    FROM tasks t
    LEFT JOIN departments d ON t.department_id = d.id
    LEFT JOIN users u ON t.user_id = u.user_id
    ORDER BY t.created_at DESC
";
$tasks = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .log-box {
            background: #f9f9f9;
            padding: 10px;
            font-size: 13px;
            border-left: 4px solid #0d6efd;
            margin: 10px 0;
        }
        .action-buttons {
            text-align: center;
            margin: 20px 0;
        }
        .action-buttons a {
            margin-right: 10px;
            padding: 6px 12px;
            background: #0d6efd;
            color: white;
            border-radius: 4px;
            text-decoration: none;
        }
        .attachment {
            font-size: 13px;
        }
    </style>
</head>
<body>

<div style="text-align: center;">
    <img src="../assets/logo.jpeg" alt="USI Logo" style="height:100px;">
    <h1>United Service Institution of India</h1>
</div>


<h2>Welcome, <?= htmlspecialchars($_SESSION['user']['name']) ?> (Admin)</h2>
<p>
    <a href="change_password.php">🔒 Change Password</a> |
    <a href="reset_passwords.php">🛠 Reset User Passwords</a> |
    <a href="../logout.php">Logout</a>
</p>

<div class="action-buttons">
    <a href="stats.php">📊 View Stats</a>
    <a href="export_tasks.php">📤 Export CSV</a>
</div>

<h3>📋 All Tasks</h3>
<table>
    <tr>
        <th>Ref No</th>
        <th>Title</th>
        <th>Department</th>
        <th>Raised By</th>
        <th>Created</th>
        <th>Status</th>
        <th>Support Staff</th>
        <th>Attachment</th>
        <th>👁️</th>
        <th>Assign</th>
        <th>Logs</th>
    </tr>
    <?php while ($row = $tasks->fetch_assoc()): ?>
    <tr class="<?= $row['status'] === 'New' ? 'flashing' : '' ?>">
        <td><?= htmlspecialchars($row['reference_no']) ?></td>
        <td><?= htmlspecialchars($row['title']) ?></td>
        <td><?= htmlspecialchars($row['department_name']) ?></td>
        <td><?= htmlspecialchars($row['raised_by']) ?></td>
        <td><?= $row['created_at'] ?></td>
        <td>
            <span class="status 
                <?= $row['status'] === 'Opened' ? 'red' : '' ?>
                <?= $row['status'] === 'Pending' ? 'yellow' : '' ?>
                <?= $row['status'] === 'Completed' ? 'green' : '' ?>">
                <?= htmlspecialchars($row['status']) ?>
            </span>
        </td>
        <td><?= htmlspecialchars($row['support_staff_id'] ?? 'Unassigned') ?></td>
        <td class="attachment">
            <?php if (!empty($row['file_path'])): ?>
                <a href="../<?= $row['file_path'] ?>" target="_blank">📎 View</a>
            <?php else: ?>
                None
            <?php endif; ?>
        </td>
        <td>
            <a href="view_task.php?id=<?= $row['task_id'] ?>">👁️</a>
        </td>
        <td>
            <?php if (empty($row['support_staff_id'])): ?>
                <form method="POST">
                    <input type="hidden" name="task_id" value="<?= $row['task_id'] ?>">
                    <select name="staff_id" required>
                        <option value="">Select</option>
                        <?php foreach ($support_staff as $staff): ?>
                            <option value="<?= $staff['user_id'] ?>"><?= htmlspecialchars($staff['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="assign">Assign</button>
                </form>
            <?php else: ?>—
            <?php endif; ?>
        </td>
        <td>
            <?php
            $task_id = $row['task_id'];
            $logs = $conn->prepare("SELECT * FROM task_logs WHERE task_id = ? ORDER BY created_at DESC");
            $logs->bind_param("i", $task_id);
            $logs->execute();
            $log_result = $logs->get_result();
            ?>
            <?php while ($log = $log_result->fetch_assoc()): ?>
                <div class="log-box">
                    <strong><?= $log['role'] ?>:</strong> <?= htmlspecialchars($log['action']) ?><br>
                    <small><?= $log['created_at'] ?></small>
                </div>
            <?php endwhile; ?>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
</body>
</html>
