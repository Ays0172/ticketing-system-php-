<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Administrator') {
    header("Location: ../index.php");
    exit;
}

$success = $error = "";

// ✅ Fetch all users except admins
$users = $conn->query("SELECT user_id, name, role FROM users WHERE role != 'Administrator'");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_user = $_POST['user_id'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($new_password) || $new_password !== $confirm_password) {
        $error = "❌ Passwords must match and not be empty.";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->bind_param("ss", $hashed, $target_user);
        if ($stmt->execute()) {
            $success = "✅ Password reset for user ID: $target_user";
        } else {
            $error = "❌ Failed to reset password.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset User Password</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .form-box {
            max-width: 600px;
            margin: 50px auto;
            padding: 25px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px #ccc;
        }
        select, input, button {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<div style="text-align: center;">
    <img src="../assets/logo.jpeg" alt="USI Logo" style="height:100px;">
    <h1>United Service Institution of India</h1>
</div>


    <div class="form-box">
        <h2>🛠 Admin Password Reset</h2>

        <?php if ($success): ?><p style="color:green;"><?= $success ?></p><?php endif; ?>
        <?php if ($error): ?><p style="color:red;"><?= $error ?></p><?php endif; ?>

        <form method="POST">
            <label>Select User:</label>
            <select name="user_id" required>
                <option value="">-- Select --</option>
                <?php while ($u = $users->fetch_assoc()): ?>
                    <option value="<?= $u['user_id'] ?>">
                        <?= htmlspecialchars($u['name']) ?> (<?= $u['role'] ?>)
                    </option>
                <?php endwhile; ?>
            </select>

            <label>New Password:</label>
            <input type="password" name="new_password" required>

            <label>Confirm Password:</label>
            <input type="password" name="confirm_password" required>

            <button type="submit">Reset Password</button>
        </form>

        <br><a href="dashboard.php">⬅ Back to Dashboard</a>
    </div>
</body>
</html>
