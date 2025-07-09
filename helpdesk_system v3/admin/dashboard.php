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

// Fetch support staff for dropdown
$support_staff = $conn->query("SELECT user_id, name FROM users WHERE role = 'Support Staff'");

// Search filter
$ref_filter = $_GET['ref'] ?? '';
$ref_filter = trim($ref_filter);

if ($ref_filter !== '') {
    $stmt = $conn->prepare("
        SELECT 
            t.id AS task_id,
            t.reference_no,
            t.title,
            t.status,
            t.created_at,
            t.support_staff_id,
            ss.name AS support_staff_name,
            ss.user_id AS support_staff_user_id,
            d.name AS department_name,
            u.name AS raised_by
        FROM tasks t
        LEFT JOIN departments d ON t.department_id = d.id
        LEFT JOIN users u ON t.user_id = u.user_id
        LEFT JOIN users ss ON t.support_staff_id = ss.user_id
        WHERE t.reference_no LIKE CONCAT('%', ?, '%')
        ORDER BY t.created_at DESC
    ");
    $stmt->bind_param("s", $ref_filter);
    $stmt->execute();
    $tasks = $stmt->get_result();
} else {
    $sql = "
        SELECT 
            t.id AS task_id,
            t.reference_no,
            t.title,
            t.status,
            t.created_at,
            t.support_staff_id,
            ss.name AS support_staff_name,
            ss.user_id AS support_staff_user_id,
            d.name AS department_name,
            u.name AS raised_by
        FROM tasks t
        LEFT JOIN departments d ON t.department_id = d.id
        LEFT JOIN users u ON t.user_id = u.user_id
        LEFT JOIN users ss ON t.support_staff_id = ss.user_id
        ORDER BY t.created_at DESC
    ";
    $tasks = $conn->query($sql);
}
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
            margin: 20px 25px;
        }
        .action-buttons a {
            margin-right: 10px;
            padding: 6px 12px;
            background: var(--primary);
            color: white;
            border-radius: 4px;
            text-decoration: none;
        }
        .search-bar {
            margin: 20px 25px;
        }
    </style>
</head>
<body>

<!-- Header -->
<div style="text-align: center;">
    <img src="../assets/logo.jpeg" alt="USI Logo" style="height:100px;">
    <h1>United Service Institution of India</h1>
</div>

<!-- Admin info -->
<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin: 20px 25px;">
    <div>
        <h2 style="margin: 0;">👋 Welcome, <?= htmlspecialchars($_SESSION['user']['name']) ?> (Admin)</h2>
        <p style="margin: 6px 0;">
            <a href="change_password.php">🔒 Change Password</a> |
            <a href="reset_passwords.php">🛠 Reset User Passwords</a>
        </p>
    </div>
    <div>
        <a href="../logout.php" class="button" style="background: var(--danger);">🚪 Logout</a>
    </div>
</div>

<!-- Buttons with Descriptions -->
<div class="action-buttons">
    <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 30px;">
        <div>
            <a href="stats.php">📊 View Task Stats (Graphs)</a>
            <p style="font-size: 13px; color: #444; margin: 5px 0 0 0;">View visual task data (status, trends).</p>
        </div>
        <div>
            <a href="export_tasks.php">📤 Export Task Data (CSV)</a>
            <p style="font-size: 13px; color: #444; margin: 5px 0 0 0;">Download full task list as CSV file.</p>
        </div>
    </div>
</div>

<!-- Search bar -->
<form method="GET" class="search-bar">
    <label for="ref"><strong>🔍 Search Task by Reference No:</strong></label>
    <input type="text" name="ref" id="ref" value="<?= htmlspecialchars($ref_filter) ?>" placeholder="Enter reference no like ITS-001" />
    <button type="submit">Search</button>
</form>

<!-- Table -->
<h3 style="margin-left: 25px;">📋 All Tasks</h3>
<table>
    <tr>
        <th>Ref No</th>
        <th>Title</th>
        <th>Department</th>
        <th>Raised By</th>
        <th>Created</th>
        <th>Status</th>
        <th>Support Staff</th>
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
        <td>
            <?php if ($row['support_staff_name']): ?>
                <?= htmlspecialchars($row['support_staff_name']) ?> (<?= htmlspecialchars($row['support_staff_user_id']) ?>)
            <?php else: ?>
                Unassigned
            <?php endif; ?>
        </td>
        <td><a href="view_task.php?id=<?= $row['task_id'] ?>">👁️</a></td>
        <td>
            <?php if (empty($row['support_staff_id'])): ?>
                <form method="POST">
                    <input type="hidden" name="task_id" value="<?= $row['task_id'] ?>">
                    <select name="staff_id" required>
                        <option value="">Select</option>
                        <?php foreach ($support_staff as $staff): ?>
                            <option value="<?= $staff['user_id'] ?>"><?= htmlspecialchars($staff['name']) ?> (<?= $staff['user_id'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="assign">Assign</button>
                </form>
            <?php else: ?>—<?php endif; ?>
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
