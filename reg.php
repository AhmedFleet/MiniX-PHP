<?php
require_once "DBTesT.php"; // ensures $conn is defined as a valid PG connection

$message = "";

function validate_input($username, $password, $confirm_password) {
    $errors = [];
    if (strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "⚠️ Username must be at least 3 characters and contain only letters, numbers, and underscores.";
    }
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors[] = "⚠️ Password must be at least 8 characters and include letters and numbers.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "⚠️ Passwords do not match.";
    }
    return $errors;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password_raw = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    $errors = validate_input($username, $password_raw, $confirm_password);

    if (!$conn) {
        $errors[] = "❌ Unable to connect to the database.";
    }

    if (empty($errors)) {
        $password = password_hash($password_raw, PASSWORD_DEFAULT);
        $check = pg_query_params($conn, "SELECT id FROM users WHERE username = $1", [$username]);
        if ($check && pg_num_rows($check) > 0) {
            $message = "⚠️ Username already exists.";
        } else {
            $insert = pg_query_params($conn, "INSERT INTO users (username, password) VALUES ($1, $2)", [$username, $password]);
            $message = $insert ? "✅ Registration successful. You can now log in." : "❌ Registration failed.";
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
    <title>User Registration</title>
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
    <h2 style="text-align:center; margin-top: 120px; margin-bottom: 8px;">Register New Account</h2>
    <form method="post" novalidate>
        <label for="username" style="font-size: 22px;">Enter your Information</label>
        <br>
        <input type="text" name="username" id="username" placeholder="Enter your username" required>
        <p class="smalltext">Username must be at least 3 characters and contain only letters, numbers, and underscores</p>
        <br><br>

        <input type="password" name="password" id="password" placeholder="Enter your password" required>
        <p class="smalltext">Password must be at least 8 characters and include both letters and numbers.</p>

        <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm your password" required>
        <p class="smalltext">Make sure both passwords match.</p>

        <div style="margin-top: 5px; display: flex; align-items: center;">
            <input type="checkbox" id="showPassword" onclick="togglePassword()">
            <label for="showPassword" style="margin-top: 8px;">Show Passwords</label>
        </div>
        <br><br>

        <button type="submit">Create Account</button>
        <br>
        <p class="smalltext" style="text-align: center;">Already have an account ? <br> <a href="log.php">Login here</a></p>
    </form>

    <?php if (!empty($message)): ?>
        <p class="message <?= str_starts_with($message, '✅') ? 'success' : 'error' ?>">
            <?= $message ?>
        </p>
    <?php endif; ?>
</body>
</html>