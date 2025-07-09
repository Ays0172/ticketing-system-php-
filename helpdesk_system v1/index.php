<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = trim($_POST['user_id']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? AND role = ?");
    $stmt->bind_param("ss", $user_id, $role);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && hash('sha256', $password) === $user['password']) {
        $_SESSION['user'] = $user;

        if ($role === 'Administrator') {
            header("Location: admin/dashboard.php");
        } elseif ($role === 'Support Staff') {
            header("Location: support/dashboard.php");
        } else {
            header("Location: user/dashboard.php");
        }
        exit;
    } else {
        $error = "Invalid credentials";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>USI Helpdesk Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div style="text-align: center;">
    <img src="../assets/logo.jpeg" alt="USI Logo" style="height:100px;">
    <h1>United Service Institution of India</h1>
</div>


    <form method="POST" style="max-width: 400px; margin: 100px auto; padding: 20px; background: white; border-radius: 10px;">
        <h2>Login</h2>
        <input type="text" name="user_id" placeholder="User ID (e.g., USI001)" required>
        <input type="password" name="password" placeholder="Password" required>
        <select name="role" required>
            <option value="">Select Role</option>
            <option value="Administrator">Administrator</option>
            <option value="Support Staff">Support Staff</option>
            <option value="User">User</option>
        </select>
        <button type="submit">Login</button>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
    </form>
</body>
</html>
