<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Administrator') {
    header("Location: ../index.php");
    exit;
}

// Optional filter
$filter = $_GET['status'] ?? '';
$where = $filter ? "WHERE t.status = '$filter'" : "";

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="tasks_export.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Reference No', 'Title', 'Department', 'Status', 'Raised By', 'Assigned To', 'Created At']);

$sql = "
    SELECT 
        t.reference_no, t.title, d.name AS department, t.status,
        u.name AS raised_by, s.name AS assigned_to, t.created_at
    FROM tasks t
    LEFT JOIN departments d ON t.department_id = d.id
    LEFT JOIN users u ON t.user_id = u.user_id
    LEFT JOIN users s ON t.support_staff_id = s.user_id
    $where
    ORDER BY t.created_at DESC
";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['reference_no'],
        $row['title'],
        $row['department'],
        $row['status'],
        $row['raised_by'],
        $row['assigned_to'] ?? 'Unassigned',
        $row['created_at']
    ]);
}

fclose($output);
exit;
