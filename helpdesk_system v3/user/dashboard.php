<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'User') {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user']['user_id'];

$tasks = $conn->prepare("
    SELECT t.*, d.name AS department_name, s.name AS support_staff
    FROM tasks t
    LEFT JOIN departments d ON t.department_id = d.id
    LEFT JOIN users s ON t.support_staff_id = s.user_id
    WHERE t.user_id = ?
    ORDER BY t.created_at DESC
");
$tasks->bind_param("s", $user_id);
$tasks->execute();
$result = $tasks->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Dashboard</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>

<div style="text-align: center;">
    <img src="../assets/logo.jpeg" alt="USI Logo" style="height:100px;">
    <h1>United Service Institution of India</h1>
</div>


    <h2>Welcome, <?= htmlspecialchars($_SESSION['user']['name']) ?> (User)</h2>
    <p>
        <a href="change_password.php">🔒 Change Password</a> | 
        <a href="../logout.php">Logout</a>
    </p>
    <a href="new_task.php">➕ Raise New Task</a>

    <h3>Your Support Requests</h3>
    <table>
        <tr>
            <th>Ref No</th>
            <th>Title</th>
            <th>Department</th>
            <th>Support Staff</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['reference_no']) ?></td>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars($row['department_name']) ?></td>
            <td><?= htmlspecialchars($row['support_staff'] ?? 'Unassigned') ?></td>
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
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
