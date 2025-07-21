<?php
require_once "DBTesT.php";
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"] ?? "";

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $newUsername = trim($_POST["username"]);
    $newPassword = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    $errors = [];

    if (strlen($newUsername) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $newUsername)) {
        $errors[] = "⚠️ Username must be at least 3 characters and contain only letters, numbers, and underscores.";
    }

    if (!empty($newPassword)) {
        if (strlen($newPassword) < 6) {
            $errors[] = "⚠️ Password must be at least 6 characters.";
        }
        if ($newPassword !== $confirmPassword) {
            $errors[] = "⚠️ Password confirmation does not match.";
        }
    }

    if (empty($errors)) {
        $params = [$newUsername];
        $query = "UPDATE users SET username = $1";
        $paramIndex = 2;

        if (!empty($newPassword)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $query .= ", password = $" . $paramIndex;
            $params[] = $hashedPassword;
            $paramIndex++;
        }

        $query .= " WHERE id = $" . $paramIndex;
        $params[] = $user_id;

        $result = pg_query_params($conn, $query, $params);

        if ($result) {
            $_SESSION["username"] = $newUsername;
            $username = $newUsername;
            $message = "✅ Profile updated successfully.";
            $messageType = "success";
        } else {
            $message = "❌ Failed to update profile.";
            $messageType = "error";
        }
    } else {
        $message = implode("<br>", array_map("htmlspecialchars", $errors));
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="style.css">
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
        <h2 style="text-align: center; margin-top: 100px;">Edit Your Profile ✏️</h2>

        <?php if (!empty($message)): ?>
            <p class="message <?= $messageType === 'success' ? 'success' : 'error' ?>">
                <?= $message ?>
            </p>
        <?php endif; ?>

        <form method="post" novalidate>
            <label for="username" style="margin-top: 2px;"> Enter New Username</label>
            <input type="text" name="username" id="username" value="<?= htmlspecialchars($username) ?>" required>

            <label for="password" style="margin-top: 10px; margin-bottom: 0px;">Enter New Password (optional)</label>
            <input type="password" name="password" id="password" placeholder="Leave blank to keep current">

            <label for="confirm_password" style="margin-top: 10px; margin-bottom: 0px;">Confirm New Password</label>
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Repeat new password">

            <div style="margin-top: 5px; display: flex;">
                <input type="checkbox" id="showPassword" onclick="togglePassword()">
                <label for="showPassword" style="margin-top: 8px;">Show Passwords</label>
            </div>

            <br>
            <button type="submit">💾 Update Profile</button>
            <p style="margin-top: 10px;"><a href="home.php">⬅️ Back to Home</a></p>
        </form>
</body>
</html>
