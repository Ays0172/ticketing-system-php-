<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Administrator') {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign'])) {
    $task_id = $_POST['task_id'];
    $staff_id = $_POST['staff_id'];

    // Update task
    $stmt = $conn->prepare("UPDATE tasks SET support_staff_id = ?, status = 'Opened' WHERE id = ?");
    $stmt->bind_param("si", $staff_id, $task_id);
    $stmt->execute();

    // Log action
    $admin_id = $_SESSION['user']['user_id'];
    $desc = "Assigned task to $staff_id";
    $role = 'Administrator';
    $log = $conn->prepare("INSERT INTO task_logs (task_id, action, actor_user_id, role) VALUES (?, ?, ?, ?)");
    $log->bind_param("isss", $task_id, $desc, $admin_id, $role);
    $log->execute();

    header("Location: dashboard.php");
    exit;
}
