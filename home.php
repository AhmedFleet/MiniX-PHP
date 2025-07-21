<?php
require_once "DBTesT.php";
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"] ?? "User";

$successMessage = $_SESSION["success"] ?? "";
unset($_SESSION["success"]);

$searchTerm = trim($_GET['search'] ?? "");
$searchResults = [];
$friendMessage = "";

// ========== إضافة منشور ==========
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['new_post_content'], $_POST['new_post_title'])) {
    $content = trim($_POST['new_post_content']);
    $title = trim($_POST['new_post_title']);
    if (!empty($content) && !empty($title)) {
        $insertPost = pg_query_params($conn, "INSERT INTO posts (user_id, title, content) VALUES ($1, $2, $3)", [$user_id, $title, $content]);
        $_SESSION["success"] = $insertPost ? "✅ Post added successfully!" : "❌ Failed to add post.";
        header("Location: home.php");
        exit;
    }
}

// ========== الإعجاب ==========
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['like_post_id'])) {
    $postId = $_POST['like_post_id'];
    $liked = pg_query_params($conn, "SELECT 1 FROM likes WHERE post_id = $1 AND user_id = $2", [$postId, $user_id]);
    if ($liked && pg_num_rows($liked) === 0) {
        pg_query_params($conn, "INSERT INTO likes (post_id, user_id) VALUES ($1, $2)", [$postId, $user_id]);
    }
    header("Location: home.php");
    exit;
}

// ========== التعليق ==========
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['comment_post_id'], $_POST['comment_text'])) {
    $postId = $_POST['comment_post_id'];
    $comment = trim($_POST['comment_text']);
    if (!empty($comment)) {
        pg_query_params($conn, "INSERT INTO comments (post_id, user_id, comment) VALUES ($1, $2, $3)", [$postId, $user_id, $comment]);
    }
    header("Location: home.php");
    exit;
}

// ========== إدارة الأصدقاء ==========
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['friend_id'], $_POST['action'])) {
    $friendId = $_POST['friend_id'];
    $action = $_POST['action'];

    if ($action === 'add') {
        $check = pg_query_params($conn, "SELECT 1 FROM friends WHERE user_id = $1 AND friend_id = $2", [$user_id, $friendId]);
        if ($check && pg_num_rows($check) === 0) {
            $insert1 = pg_query_params($conn, "INSERT INTO friends (user_id, friend_id) VALUES ($1, $2)", [$user_id, $friendId]);
            $insert2 = pg_query_params($conn, "INSERT INTO friends (user_id, friend_id) VALUES ($1, $2)", [$friendId, $user_id]);
            $friendMessage = ($insert1 && $insert2) ? "✅ Friend added!" : "❌ Failed.";
        } else {
            $friendMessage = "⚠️ Already friends.";
        }
    } elseif ($action === 'remove') {
        $delete1 = pg_query_params($conn, "DELETE FROM friends WHERE user_id = $1 AND friend_id = $2", [$user_id, $friendId]);
        $delete2 = pg_query_params($conn, "DELETE FROM friends WHERE user_id = $1 AND friend_id = $2", [$friendId, $user_id]);
        $friendMessage = ($delete1 && $delete2) ? "✅ Friend removed!" : "❌ Failed.";
    }
}

// ========== نتائج البحث ==========
if (!empty($searchTerm)) {
    $res = pg_query_params($conn, "SELECT id, username FROM users WHERE username ILIKE $1 AND id != $2", ["%$searchTerm%", $user_id]);
    while ($row = pg_fetch_assoc($res)) {
        $searchResults[] = $row;
    }
}

// ========== استعلامات البيانات ==========
$friendsListResult = pg_query_params($conn, "SELECT u.id, u.username FROM users u JOIN friends f ON f.friend_id = u.id WHERE f.user_id = $1", [$user_id]);
$myPostsResult = pg_query_params($conn, "SELECT p.id, p.title, p.content, p.created_at, (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS like_count FROM posts p WHERE user_id = $1 ORDER BY created_at DESC", [$user_id]);
$friendPostsResult = pg_query_params($conn, "SELECT p.id, p.title, p.content, p.created_at, u.username, (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS like_count FROM posts p JOIN users u ON p.user_id = u.id WHERE p.user_id IN (SELECT friend_id FROM friends WHERE user_id = $1) ORDER BY p.created_at DESC", [$user_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
    <div class="top-bar">
        <h2>Welcome <?= htmlspecialchars($username) ?> 👋</h2>
        <a class="edit-profile" href="edit_profile.php" style="color: var(--info);">Edit Profile ✏️</a>
        <a class="logout" href="logout.php" style="color: var(--danger);">Logout 🚪</a>
    </div>

    <?php if ($successMessage): ?><p class="message success"><?= $successMessage ?></p><?php endif; ?>
    <?php if ($friendMessage): ?><p class="message success"><?= $friendMessage ?></p><?php endif; ?>

    <div class="friends-panel">
        <h3>Your Friends</h3>
        <?php while ($f = pg_fetch_assoc($friendsListResult)): ?>
            <div class="friend-item">
                <span><?= htmlspecialchars($f['username']) ?></span>
                <form method="post" class="friend-action-form">
                    <input type="hidden" name="friend_id" value="<?= $f['id'] ?>">
                    <input type="hidden" name="action" value="remove">
                    <button class="remove-button" type="submit" style="background-color: white; color: red; border: 2px solid red;">❌ Remove</button>
                </form>
            </div>
        <?php endwhile; ?>

        <form class="friends-search-form" method="get" action="home.php">
            <input type="text" name="search" placeholder="Search friend..." value="<?= htmlspecialchars($searchTerm) ?>">
            <button class="friends-search-button" type="submit">🔍</button>
        </form>

        <?php foreach ($searchResults as $user): ?>
            <div class="search-result">
                <span><?= htmlspecialchars($user['username']) ?></span>
                <form method="post" class="friend-action-form">
                    <input type="hidden" name="friend_id" value="<?= $user['id'] ?>">
                    <input type="hidden" name="action" value="add">
                    <button class="add-button" type="submit">➕ Add</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="New-Post">
        <h3>➕ New Post</h3>
        <form method="post" class="New-Post-form">
            <input type="text" name="new_post_title" placeholder="Title" required><br>
            <textarea name="new_post_content" placeholder="What's on your mind?" required></textarea><br>
            <button type="submit">📝 Publish</button>
        </form>
    </div>

<div class="friends-posts">
    <h3 style="text-align: center;">My Posts</h3>
    <?php while ($post = pg_fetch_assoc($myPostsResult)): ?>
        <div class="post">
            <h4><?= htmlspecialchars($post['title']) ?></h4>
            <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>
            <small><?= htmlspecialchars($post['created_at']) ?> | ❤️ <?= $post['like_count'] ?></small>

            <!-- عرض التعليقات المرتبطة بهذا المنشور -->
            <?php
            $comments = pg_query_params($conn, "
                SELECT c.comment, u.username FROM comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.post_id = $1 ORDER BY c.created_at ASC
            ", [$post['id']]);
            while ($c = pg_fetch_assoc($comments)): ?>
                <div class="comment">
                    <strong><?= htmlspecialchars($c['username']) ?>:</strong> <?= htmlspecialchars($c['comment']) ?>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endwhile; ?>
</div>


    <div class="friends-posts">
        <h3>Friends' Posts</h3>
        <?php while ($post = pg_fetch_assoc($friendPostsResult)): ?>
            <div class="post">
                <h4><?= htmlspecialchars($post['title']) ?></h4>
                <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                <small><?= htmlspecialchars($post['username']) ?> - <?= $post['created_at'] ?> | ❤️ <?= $post['like_count'] ?></small>
                <form method="post" class="like-form">
                    <input type="hidden" name="like_post_id" value="<?= $post['id'] ?>">
                    <button type="submit">❤️ Like</button>
                </form>
                <form method="post" class="comment-form">
                    <input type="hidden" name="comment_post_id" value="<?= $post['id'] ?>">
                    <input type="text" name="comment_text" placeholder="Add comment" required>
                    <button type="submit">💬</button>
                </form>
                <?php
                $comments = pg_query_params($conn, "SELECT c.comment, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = $1 ORDER BY c.created_at ASC", [$post['id']]);
                while ($c = pg_fetch_assoc($comments)): ?>
                    <div class="comment"><strong><?= htmlspecialchars($c['username']) ?>:</strong> <?= htmlspecialchars($c['comment']) ?></div>
                <?php endwhile; ?>
            </div>
        <?php endwhile; ?>
    </div>
</div>
</body>
</html>
