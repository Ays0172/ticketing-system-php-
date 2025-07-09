<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Support Staff') {
    header("Location: ../index.php");
    exit;
}

$support_id = $_SESSION['user']['user_id'];

// ✅ Update task status or reassign
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $task_id = $_POST['task_id'];
        $new_status = $_POST['status'];

        $stmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ? AND support_staff_id = ?");
        $stmt->bind_param("sis", $new_status, $task_id, $support_id);
        $stmt->execute();

        $log = $conn->prepare("INSERT INTO task_logs (task_id, action, actor_user_id, role) VALUES (?, ?, ?, ?)");
        $desc = "Status updated to '$new_status'";
        $role = 'Support Staff';
        $log->bind_param("isss", $task_id, $desc, $support_id, $role);
        $log->execute();
    }

    if (isset($_POST['reassign'])) {
        $task_id = $_POST['task_id'];
        $new_staff_id = $_POST['new_staff_id'];

        $stmt = $conn->prepare("UPDATE tasks SET support_staff_id = ? WHERE id = ? AND support_staff_id = ?");
        $stmt->bind_param("sii", $new_staff_id, $task_id, $support_id);
        $stmt->execute();

        $log = $conn->prepare("INSERT INTO task_logs (task_id, action, actor_user_id, role) VALUES (?, ?, ?, ?)");
        $desc = "Reassigned ticket to $new_staff_id";
        $role = 'Support Staff';
        $log->bind_param("isss", $task_id, $desc, $support_id, $role);
        $log->execute();
    }
}

// ✅ Fetch staff list
$staffList = $conn->query("SELECT user_id, name FROM users WHERE role = 'Support Staff' AND user_id != '$support_id'");

// ✅ Fetch assigned tasks
$tasks = $conn->prepare("
    SELECT t.*, d.name AS department_name, u.name AS raised_by
    FROM tasks t
    JOIN departments d ON t.department_id = d.id
    JOIN users u ON t.user_id = u.user_id
    WHERE t.support_staff_id = ?
    ORDER BY t.created_at DESC
");
$tasks->bind_param("s", $support_id);
$tasks->execute();
$result = $tasks->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Support Staff Dashboard</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        form.inline-form {
            display: inline-block;
        }
    </style>
</head>
<body>

<div style="text-align: center;">
    <img src="../assets/logo.jpeg" alt="USI Logo" style="height:100px;">
    <h1>United Service Institution of India</h1>
</div>


    <h2>Welcome, <?= htmlspecialchars($_SESSION['user']['name']) ?> (Support Staff)</h2>
    <p>
        <a href="change_password.php">🔒 Change Password</a> | 
        <a href="../logout.php">Logout</a>
    </p>

    <h3>Your Assigned Tasks</h3>
    <table>
        <tr>
            <th>Ref No</th>
            <th>Title</th>
            <th>Department</th>
            <th>Raised By</th>
            <th>Status</th>
            <th>👁️ View</th>
            <th>Update</th>
            <th>Reassign</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['reference_no']) ?></td>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars($row['department_name']) ?></td>
            <td><?= htmlspecialchars($row['raised_by']) ?></td>
            <td>
                <span class="status 
                    <?= $row['status'] === 'Opened' ? 'red' : '' ?>
                    <?= $row['status'] === 'Pending' ? 'yellow' : '' ?>
                    <?= $row['status'] === 'Completed' ? 'green' : '' ?>">
                    <?= htmlspecialchars($row['status']) ?>
                </span>
            </td>
            <td>
                <a href="view_task.php?id=<?= $row['id'] ?>">👁️ View</a>
            </td>
            <td>
                <form method="POST" class="inline-form">
                    <input type="hidden" name="task_id" value="<?= $row['id'] ?>">
                    <select name="status" required>
                        <option value="">Change</option>
                        <option value="Pending">Pending</option>
                        <option value="Completed">Completed</option>
                    </select>
                    <button type="submit" name="update_status">Update</button>
                </form>
            </td>
            <td>
                <form method="POST" class="inline-form">
                    <input type="hidden" name="task_id" value="<?= $row['id'] ?>">
                    <select name="new_staff_id" required>
                        <option value="">Select</option>
                        <?php foreach ($staffList as $staff): ?>
                            <option value="<?= $staff['user_id'] ?>"><?= htmlspecialchars($staff['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="reassign">Reassign</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
