<?php
session_start();
include('connection.php');



$currentUserId = $_SESSION['userId'];
$displayName = $_SESSION['userName'];

// Create Post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_create_post'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $location = trim($_POST['location']);
   

    if (!empty($title) && !empty($content)) {
        $stmt = $conn->prepare("INSERT INTO posts (userId, title, description, location, status) VALUES ( ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("isss", $currentUserId, $title, $content, $location);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: homepage.php");
    exit();
}

// Create Comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_comment'])) {
    $postId = intval($_POST['postId']);
    $commentText = trim($_POST['comment']);

    if ($postId > 0 && !empty($commentText)) {
        $stmt = $conn->prepare("INSERT INTO comments (postId, userId, commentText) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $postId, $currentUserId, $commentText);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: homepage.php");
    exit();
}

// Fetch post that admin approved
$postsResult = $conn->query("
    SELECT p.*, u.userName 
    FROM posts p 
    JOIN users u ON p.userId = u.userId 
    WHERE p.status = 'approved' 
    ORDER BY p.postId DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - News Feed</title>
    <link rel="stylesheet" href="./styles/style.css">
</head>
<body class="dashboard-body">

<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="brand">
            <span>📰 Student Portal</span>
            <small class="brand-subtitle">Disaster Management System</small>
        </div>

        <div class="user-profile">
            <div class="user-avatar"><?php echo strtoupper(substr($displayName, 0, 1)); ?></div>
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($displayName); ?></span>
                <a href="index.php" class="logout-link">Logout</a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Hello, <?php echo htmlspecialchars($displayName); ?>!</h1>
            <p class="page-subtitle">View approved alerts or report a new disaster incident.</p>
        </div>

        <div class="tab-buttons">
            <button type="button" class="tab-button active" data-tab="news">Recent News</button>
            <button type="button" class="tab-button" data-tab="report">Report a Disaster</button>
        </div>

        <!-- News Feed Tab -->
        <div class="tab-content active" id="news-tab">
            <div class="content-card panel-card">
                <div class="card-header">
                    <h3>Approved Posts</h3>
                </div>
                <div class="card-body">
                    <?php if ($postsResult && $postsResult->num_rows > 0): ?>
                        <?php while ($post = $postsResult->fetch_assoc()): ?>
                            <div class="content-card post-card">
                                <div class="post-header">
                                    <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                                    <?php if (!empty($post['severity'])): ?>
                                        <div class="severity-pill"><?php echo strtoupper($post['severity']); ?></div>
                                    <?php endif; ?>
                                    <span class="post-meta">Posted by <strong><?php echo htmlspecialchars($post['userName']); ?></strong></span>
                                </div>

                                <p class="post-body"><?php echo htmlspecialchars($post['description']); ?></p>
                                
                                <?php if (!empty($post['location'])): ?>
                                    <div class="post-location">📍 <?php echo htmlspecialchars($post['location']); ?></div>
                                <?php endif; ?>

                                <hr>

                                <!-- Comments Section -->
                                <div class="comments-section">
                                    <h4>Comments</h4>
                                    <?php
                                    $currentPostId = $post['postId'];
                                    $commentRes = $conn->query("
                                        SELECT c.*, u.userName 
                                        FROM comments c 
                                        JOIN users u ON c.userId = u.userId 
                                        WHERE c.postId = $currentPostId
                                    ");
                                    ?>

                                    <?php if ($commentRes && $commentRes->num_rows > 0): ?>
                                        <div class="comments-list">
                                            <?php while ($c = $commentRes->fetch_assoc()): ?>
                                                <div class="comment-card">
                                                    <strong><?php echo htmlspecialchars($c['userName']); ?>:</strong>
                                                    <span><?php echo htmlspecialchars($c['commentText']); ?></span>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                    <?php else: ?>
                                        <p style="color: #94a3b8;">No comments yet.</p>
                                    <?php endif; ?>

                                    <!-- Comment Form -->
                                    <form action="homepage.php" method="POST" class="comment-form">
                                        <input type="hidden" name="action_add_comment" value="1">
                                        <input type="hidden" name="postId" value="<?php echo $currentPostId; ?>">
                                        <input type="text" name="comment" placeholder="Write a comment..." class="comment-input" required>
                                        <button type="submit" class="btn btn-save">Comment</button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No news posts available right now.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Report Incident Tab -->
        <div class="tab-content" id="report-tab">
            <div class="content-card panel-card">
                <div class="card-header">
                    <h3>Report a New Incident</h3>
                </div>
                <div class="card-body">
                    <form action="homepage.php" method="POST">
                        <input type="hidden" name="action_create_post" value="1">

                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="title" class="form-input" required>
                        </div>

                       
                        <div class="form-group">
                            <label>Location</label>
                            <input type="text" name="location" class="form-input">
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="content" rows="4" class="form-textarea" required></textarea>
                        </div>

                        <button type="submit" class="btn btn-save">Submit Report</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    // Tab switching JS
    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', () => {
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

            button.classList.add('active');
            document.getElementById(`${button.dataset.tab}-tab`).classList.add('active');
        });
    });
</script>
</body>
</html>