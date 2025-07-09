<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'User') {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user']['user_id'];
$departments = $conn->query("SELECT * FROM departments");

// 🆕 Fetch all previous tickets for dropdown
$previous_stmt = $conn->prepare("SELECT id, reference_no, title FROM tasks WHERE user_id = ? ORDER BY created_at DESC");
$previous_stmt->bind_param("s", $user_id);
$previous_stmt->execute();
$previous_tasks = $previous_stmt->get_result();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $designation = trim($_POST['designation']);
    $description = trim($_POST['description']);
    $department_id = (int)$_POST['department_id'];
    $related_ticket_id = !empty($_POST['related_ticket_id']) ? (int)$_POST['related_ticket_id'] : null; // 🆕

    $file_paths = [];

    // ✅ Multi-file upload
    if (!empty($_FILES['attachments']['name'][0])) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf', 'docx', 'doc'];

        foreach ($_FILES['attachments']['name'] as $i => $name_file) {
            $tmp = $_FILES['attachments']['tmp_name'][$i];
            $ext = strtolower(pathinfo($name_file, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed_ext)) {
                $new_name = uniqid('file_') . '.' . $ext;
                $destination = '../uploads/' . $new_name;

                if (move_uploaded_file($tmp, $destination)) {
                    $file_paths[] = 'uploads/' . $new_name;
                }
            }
        }
    }

    // ✅ Get department short code & name
    $dept_stmt = $conn->prepare("SELECT short_code, name FROM departments WHERE id = ?");
    $dept_stmt->bind_param("i", $department_id);
    $dept_stmt->execute();
    $dept_result = $dept_stmt->get_result()->fetch_assoc();

    if (!$dept_result) {
        die("❌ Invalid department.");
    }

    $prefix = $dept_result['short_code'];
    $department_name = $dept_result['name'];

    // ✅ Generate reference no
    $count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM tasks WHERE department_id = ?");
    $count_stmt->bind_param("i", $department_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result()->fetch_assoc();
    $total = (int)$count_result['total'] + 1;

    $reference_no = $prefix . '-' . str_pad($total, 3, '0', STR_PAD_LEFT);
    $title = "Support Request - $department_name - $reference_no";
    $status = 'New';
    $json_files = json_encode($file_paths);

    // ✅ Insert with related_ticket_id
    $insert_stmt = $conn->prepare("
        INSERT INTO tasks (user_id, raised_by_name, designation, department_id, title, description, reference_no, status, file_path, related_ticket_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insert_stmt->bind_param("sssssssssi", $user_id, $name, $designation, $department_id, $title, $description, $reference_no, $status, $json_files, $related_ticket_id); // 🆕

    if ($insert_stmt->execute()) {
        header("Location: dashboard.php");
        exit;
    } else {
        echo "<p style='color:red;'>❌ Error: " . $insert_stmt->error . "</p>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Raise New Task</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px #ccc;
        }
        label {
            font-weight: bold;
        }
    </style>
</head>
<body>

<div style="text-align: center;">
    <img src="../assets/logo.jpeg" alt="USI Logo" style="height:100px;">
    <h1>United Service Institution of India</h1>
</div>

<div class="form-container">
    <h2>Raise a New Support Request</h2>
    <form method="POST" enctype="multipart/form-data">
        <label>Your Name:</label><br>
        <input type="text" name="name" required><br><br>

        <label>Designation:</label><br>
        <input type="text" name="designation" required><br><br>

        <label>Description:</label><br>
        <textarea name="description" required></textarea><br><br>

        <label>Department:</label><br>
        <select name="department_id" required>
            <option value="">-- Select Department --</option>
            <?php while($d = $departments->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($d['id']) ?>">
                    <?= htmlspecialchars($d['name']) ?>
                </option>
            <?php endwhile; ?>
        </select><br><br>

        <!-- 🆕 Add Related Ticket Dropdown -->
        <label>Link to Previous Ticket (optional):</label><br>
        <select name="related_ticket_id">
            <option value="">-- None --</option>
            <?php while($t = $previous_tasks->fetch_assoc()): ?>
                <option value="<?= $t['id'] ?>">#<?= htmlspecialchars($t['reference_no']) ?> — <?= htmlspecialchars($t['title']) ?></option>
            <?php endwhile; ?>
        </select><br><br>

        <label>Attachments (Max 10 files):</label><br>
        <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"><br><br>

        <button type="submit">Submit</button>
    </form>

    <br><a href="dashboard.php">⬅ Back to Dashboard</a>
</div>
</body>
</html>
