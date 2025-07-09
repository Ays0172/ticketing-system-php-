<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user']['user_id'];
$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST['old_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    // ✅ Fetch hashed password from DB
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // ✅ Validate input
    if (!$user || !password_verify($old, $user['password'])) {
        $error = "❌ Incorrect old password.";
    } elseif ($new !== $confirm) {
        $error = "❌ New passwords do not match.";
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $update->bind_param("ss", $hashed, $user_id);
        if ($update->execute()) {
            $success = "✅ Password changed successfully.";
            // Optional: update session so user remains logged in
            $_SESSION['user']['password'] = $hashed;
        } else {
            $error = "❌ Failed to update password.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Change Password</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .form-box {
            max-width: 500px;
            margin: 60px auto;
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 10px #ccc;
        }
    </style>
</head>
<body>

<div style="text-align: center;">
    <img src="../assets/logo.jpeg" alt="USI Logo" style="height:100px;">
    <h1>United Service Institution of India</h1>
</div>


    <div class="form-box">
        <h2>🔐 Change Your Password</h2>

        <?php if ($success): ?><p style="color: green;"><?= $success ?></p><?php endif; ?>
        <?php if ($error): ?><p style="color: red;"><?= $error ?></p><?php endif; ?>

        <form method="POST">
            <label>Old Password:</label><br>
            <input type="password" name="old_password" required><br><br>

            <label>New Password:</label><br>
            <input type="password" name="new_password" required><br><br>

            <label>Confirm New Password:</label><br>
            <input type="password" name="confirm_password" required><br><br>

            <button type="submit">Update</button>
        </form>

        <br><a href="dashboard.php">⬅ Back to Dashboard</a>
    </div>
</body>
</html>
