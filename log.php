<?php
require_once "DBTesT.php";
session_start(); // ضروري لاستخدام الجلسة

$message = "";

function validate_input($username, $password) {
    $errors = [];
    if (strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "⚠️ Username must be at least 3 characters and contain only letters, numbers, and underscores.";
    }
    if ($password === '') {
        $errors[] = "⚠️ Password cannot be empty.";
    }
    return $errors;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password_raw = $_POST["password"] ?? "";

    $errors = validate_input($username, $password_raw);

    if (!$conn) {
        $errors[] = "❌ Unable to connect to the database.";
    }

    if (empty($errors)) {
        // نجلب أيضاً id من جدول users
        $result = pg_query_params($conn, "SELECT id, password FROM users WHERE username = $1", [$username]);
        if ($result && pg_num_rows($result) === 1) {
            $row = pg_fetch_assoc($result);
            if (password_verify($password_raw, $row['password'])) {
                // ✅ تسجيل الدخول ناجح
                $_SESSION["username"] = $username;
                $_SESSION["user_id"] = $row['id']; // ← هذه هي الإضافة الأهم
                $_SESSION["success"] = "✅ Login successful!";

                header("Location: home.php");
                exit;
            } else {
                $message = "❌ Invalid username or password.";
            }
        } else {
            $message = "❌ Invalid username or password.";
        }
    } else {
        $message = implode('<br>', array_map('htmlspecialchars', $errors));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Login</title>
    <link rel="stylesheet" href="Style.css">
    <script>
        function togglePassword() {
            const pw = document.getElementById("password");
            const cpw = document.getElementById("confirm_password");
            pw.type = pw.type === "password" ? "text" : "password";
            cpw.type = cpw.type === "password" ? "text" : "password";
        }
    </script>
</head>
<body>
    <hr>
    <h2 style="text-align:center; margin-top: 120px; margin-bottom: 8px;">Login To Your Account</h2>
    <form method="post" novalidate>
        <label for="username" style="font-size: 22px;">Enter Your Credentials</label>
        <br>
        <input type="text" name="username" id="username" placeholder="Enter your username" required>
        <br><br><br><br>
        <input type="password" name="password" id="password" placeholder="Enter your password" required>
        <p class="smalltext">Password is case-sensitive.</p>
        <di style="margin-top: 5px; display: flex;">
                <input type="checkbox" id="showPassword" onclick="togglePassword()">
                <label for="showPassword" style="margin-top: 8px;">Show Passwords</label>
            </di>
        <br><br>
        <button type="submit">Sign In</button>
        <p class="smalltext" style="text-align: center;">
            Don't have an account yet?<br><a href="reg.php">Create One</a>
        </p>
    </form>

    <?php if (!empty($message)): ?>
        <p class="message <?= str_starts_with($message, '✅') ? 'success' : 'error' ?>">
            <?= $message ?>
        </p>
    <?php endif; ?>
</body>
</html>