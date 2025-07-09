<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Support Staff') {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user']['user_id'];
$task_id = intval($_GET['id'] ?? 0);

if ($task_id <= 0) die("❌ Invalid task ID.");

// Fetch eligible users (admins + support staff)
$users = $conn->query("SELECT user_id, name, role FROM users WHERE role IN ('Support Staff', 'Administrator')");

// On submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_assignee = intval($_POST['new_assignee']);
    
    $stmt = $conn->prepare("UPDATE tasks SET support_staff_id = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_assignee, $task_id);
    $stmt->execute();

    // Log the reassignment
    $stmt = $conn->prepare("INSERT INTO task_logs (task_id, actor_user_id, action) VALUES (?, ?, ?)");
    $action = "Task reassigned to user_id $new_assignee by Support Staff user_id $user_id";
    $stmt->bind_param("iis", $task_id, $user_id, $action);
    $stmt->execute();

    header("Location: view_task.php?id=$task_id");
    exit;
}
?>

<form method="POST">
    <label><strong>Reassign Task To:</strong></label><br>
    <select name="new_assignee" required>
        <?php while ($u = $users->fetch_assoc()): ?>
            <option value="<?= $u['user_id'] ?>"><?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['role']) ?>)</option>
        <?php endwhile; ?>
    </select><br><br>
    <button type="submit">Reassign</button>
</form>
