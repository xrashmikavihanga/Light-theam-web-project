<?php
mysqli_report(MYSQLI_REPORT_OFF);
session_start();
// Retrieve and clear session feedback messages
$feedbackMsg = $_SESSION['feedback_msg'] ?? '';
$errorMsg = $_SESSION['error_msg'] ?? '';
unset($_SESSION['feedback_msg'], $_SESSION['error_msg']);
include('connection.php');

// 1. Session Protection
if (!isset($_SESSION['userId'])) {
    header("Location: index.php");
    exit();
}

$currentUserId = $_SESSION['userId'];
$feedbackMsg = '';
$errorMsg = '';

// 2. Safely Fetch Current User Name
$displayName = $_SESSION['userName'] ?? 'User';
$userQuery = $conn->prepare("
    SELECT u.userName, ud.fullName 
    FROM users u 
    LEFT JOIN userData ud ON u.userId = ud.userId 
    WHERE u.userId = ?
");

if ($userQuery) {
    $userQuery->bind_param("i", $currentUserId);
    $userQuery->execute();
    $userResult = $userQuery->get_result()->fetch_assoc();
    if ($userResult) {
        $displayName = !empty($userResult['fullName']) ? $userResult['fullName'] : $userResult['userName'];
    }
    $userQuery->close();
}
$userInitial = strtoupper(substr($displayName, 0, 1));

// ==========================================================================
// FORM SUBMIT HANDLERS
// ==========================================================================

// Handle New Post Creation

 if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_create_post'])) {
     $title = trim($_POST['title'] ?? '');
     $content = trim($_POST['content'] ?? '');
     $location = trim($_POST['location'] ?? '');
     $severity = trim($_POST['severity'] ?? 'medium');

    if (!empty($title) && !empty($content)) {
        // Attempt insert including optional severity column
        $stmt = $conn->prepare("INSERT INTO posts (userId, title, description, location, severity, status) VALUES (?, ?, ?, ?, ?, 'pending')");

        // Secondary fallback without severity
        if (!$stmt) {
            $stmt = $conn->prepare("INSERT INTO posts (userId, title, description, location, status) VALUES (?, ?, ?, ?, 'pending')");
        }

        // Final fallback for alternate column names (content)
        if (!$stmt) {
            $stmt = $conn->prepare("INSERT INTO posts (userId, title, content, location, status) VALUES (?, ?, ?, ?, 'pending')");
        }

        if ($stmt) {
            // Bind based on how many parameters the prepared statement expects
            $expected = $stmt->param_count ?? 0;
            if ($expected === 5) {
                $stmt->bind_param("issss", $currentUserId, $title, $content, $location, $severity);
            } else {
                // Default to 4 params: userId, title, content/description, location
                $stmt->bind_param("isss", $currentUserId, $title, $content, $location);
            }
            if ($stmt->execute()) {
                $_SESSION['feedback_msg'] = "Your post has been submitted and is pending admin approval!";
            } else {
                $_SESSION['error_msg'] = "Execution failed: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['error_msg'] = "Database error: " . $conn->error;
        }
    } else {
        $_SESSION['error_msg'] = "Title and content cannot be empty.";
    }

    // Redirect to prevent duplicate form submissions on page refresh
    header("Location: homepage.php");
    exit();
}

// Handle New Comment Creation
// Handle New Comment Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_comment'])) {
    $postId = (int)($_POST['postId'] ?? 0);
    $commentText = trim($_POST['comment'] ?? '');

    if ($postId > 0 && !empty($commentText)) {
        // Updated query using 'commentText' matching your DB schema
        $stmt = $conn->prepare("INSERT INTO comments (postId, userId, commentText) VALUES (?, ?, ?)");

        // Fallback for snake_case column variations
        if (!$stmt) {
            $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, commentText) VALUES (?, ?, ?)");
        }

        if ($stmt) {
            $stmt->bind_param("iis", $postId, $currentUserId, $commentText);
            if ($stmt->execute()) {
                $_SESSION['feedback_msg'] = "Comment added successfully!";
            } else {
                $_SESSION['error_msg'] = "Failed to add comment: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['error_msg'] = "Database error adding comment: " . $conn->error;
        }
    } else {
        $_SESSION['error_msg'] = "Comment text cannot be empty.";
    }

    header("Location: homepage.php");
    exit();
}
// ==========================================================================
// FETCH APPROVED POSTS
// ==========================================================================
// ==========================================================================
// FETCH APPROVED POSTS
// ==========================================================================
$postsQuery = "
    SELECT p.*, u.userName, COALESCE(ud.fullName, u.userName) AS authorName
    FROM posts p
    JOIN users u ON p.userId = u.userId
    LEFT JOIN userData ud ON u.userId = ud.userId
    WHERE p.status = 'approved'
    ORDER BY p.createdAt DESC
";
$postsResult = $conn->query($postsQuery);

// Fallback 1: Try with created_at if createdAt fails
if (!$postsResult) {
    $postsQueryFallback = "
        SELECT p.*, u.userName, COALESCE(ud.fullName, u.userName) AS authorName
        FROM posts p
        JOIN users u ON p.userId = u.userId
        LEFT JOIN userData ud ON u.userId = ud.userId
        WHERE p.status = 'approved'
        ORDER BY p.created_at DESC
    ";
    $postsResult = $conn->query($postsQueryFallback);
}

// Fallback 2: Try without explicit ORDER BY if date columns differ
if (!$postsResult) {
    $postsQueryFallback2 = "
        SELECT p.*, u.userName, COALESCE(ud.fullName, u.userName) AS authorName
        FROM posts p
        JOIN users u ON p.userId = u.userId
        LEFT JOIN userData ud ON u.userId = ud.userId
        WHERE p.status = 'approved'
    ";
    $postsResult = $conn->query($postsQueryFallback2);
}

if (!$postsResult) {
    $errorMsg = "Query execution error: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - News Feed</title>
    <link rel="stylesheet" href="./styles/style.css?v=<?php echo time(); ?>">
</head>
<body class="dashboard-body">

<div class="dashboard-wrapper">
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="brand">
            <span>📰 Student Portal</span>
            <small class="brand-subtitle">Group 21 | Disaster Management Project</small>
        </div>

        <div class="user-profile">
            <div class="user-avatar"><?php echo htmlspecialchars($userInitial); ?></div>
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($displayName); ?></span>
                <a href="logout.php" class="logout-link">Logout / Back to Login</a>
                <a href="contact.php" class="sidebar-link">Contact & Team Info</a>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Hello, <?php echo htmlspecialchars($displayName); ?>!</h1>
            <p class="page-subtitle">Share a new alert or review the latest approved posts from your community.</p>
        </div>

        <div class="welcome-banner">
            <span>👋</span>
            <div>
                Welcome to your dashboard. Create a new post on the left, and browse verified alerts on the right.
            </div>
        </div>

        <?php if (!empty($feedbackMsg)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($feedbackMsg); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($errorMsg); ?>
            </div>
        <?php endif; ?>

        <div class="tab-buttons">
            <button type="button" class="tab-button active" data-tab="news">Recent News</button>
            <button type="button" class="tab-button" data-tab="report">Report a Disaster</button>
        </div>

        <div class="tab-content active" id="news-tab">
            <div class="content-card panel-card">
                <div class="card-header">
                    <h3>Approved Posts</h3>
                    <p class="panel-description">Browse the latest approved alerts and add comments to help coordinate response.</p>
                </div>
                <div class="card-body">
                    <?php if ($postsResult && $postsResult->num_rows > 0): ?>
            <?php while ($post = $postsResult->fetch_assoc()): ?>
                <?php 
                    $currentPostId = $post['postId'] ?? $post['post_id'] ?? $post['id'] ?? 0;
                    $postDate = $post['createdAt'] ?? $post['created_at'] ?? '';
                    $postBody = !empty($post['content']) ? $post['content'] : ($post['description'] ?? '');
                ?>
                <div class="content-card post-card">
                    <div class="post-header">
                        <div>
                            <h3 class="post-title"><?php echo htmlspecialchars($post['title'] ?? 'Untitled'); ?></h3>
                            <?php if (!empty($post['severity'])): ?>
                                <div class="severity-pill <?php echo strtolower(htmlspecialchars($post['severity'])); ?>"><?php echo htmlspecialchars(strtoupper($post['severity'])); ?></div>
                            <?php endif; ?>
                            <span class="post-meta">
                                Posted by <strong><?php echo htmlspecialchars($post['authorName']); ?></strong>
                                <?php if (!empty($postDate)): ?>
                                    on <?php echo date('M d, Y - h:i A', strtotime($postDate)); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>

                    <p class="post-body"><?php echo htmlspecialchars($postBody); ?></p>
                            <?php if (!empty($post['location'])): ?>
                                <div class="post-location">📍 <?php echo htmlspecialchars($post['location']); ?></div>
                            <?php endif; ?>

                    <div class="post-divider"></div>

                    <div class="comments-section">
                        <h4 class="comments-heading">Comments</h4>

                        <?php
                        $comments = false;
                        if ($currentPostId > 0) {
                            $commentStmt = $conn->prepare("
                                SELECT c.*, COALESCE(ud.fullName, u.userName) AS commentAuthor
                                FROM comments c
                                JOIN users u ON c.userId = u.userId
                                LEFT JOIN userData ud ON u.userId = ud.userId
                                WHERE c.postId = ?
                            ");
                            if ($commentStmt) {
                                $commentStmt->bind_param("i", $currentPostId);
                                $commentStmt->execute();
                                $comments = $commentStmt->get_result();
                            }
                        }
                        ?>

                        <?php if ($comments && $comments->num_rows > 0): ?>
                            <div class="comments-list">
                                <?php while ($comment = $comments->fetch_assoc()): ?>
                                    <?php 
                                        $commentDate = $comment['createdAt'] ?? $comment['created_at'] ?? ''; 
                                        $commentBody = $comment['commentText'] ?? $comment['comment'] ?? '';
                                    ?>
                                    <div class="comment-card">
                                        <div class="comment-header">
                                            <strong class="comment-author"><?php echo htmlspecialchars($comment['commentAuthor']); ?></strong>
                                            <span class="comment-date"><?php echo !empty($commentDate) ? date('M d, h:i A', strtotime($commentDate)) : ''; ?></span>
                                        </div>
                                        <p class="comment-text"><?php echo htmlspecialchars($commentBody); ?></p>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="comments-empty">No comments yet. Be the first to comment!</p>
                        <?php endif; ?>

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
            <div class="content-card empty-feed">
                No news posts available right now.
            </div>
        <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="tab-content" id="report-tab">
            <div class="content-card panel-card">
                <div class="card-header">
                    <h3>Report a New Incident</h3>
                    <p class="panel-description">Submit a new update for admin review. Your post will be published after approval.</p>
                </div>
                <div class="card-body">
                    <form action="homepage.php" method="POST" enctype="multipart/form-data" class="post-create-card">
                        <input type="hidden" name="action_create_post" value="1">

                        <div class="form-row">
                            <div class="form-group half-width">
                                <label for="title">Title</label>
                                <input type="text" name="title" id="title" placeholder="Short descriptive title" class="form-input" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group half-width">
                                <label for="severity">Severity</label>
                                <select name="severity" id="severity" class="styled-select">
                                    <option value="low" <?php if(($_POST['severity'] ?? '') === 'low') echo 'selected'; ?>>Low</option>
                                    <option value="medium" <?php if(($_POST['severity'] ?? '') === 'medium' || empty($_POST['severity'])) echo 'selected'; ?>>Medium</option>
                                    <option value="high" <?php if(($_POST['severity'] ?? '') === 'high') echo 'selected'; ?>>High</option>
                                    <option value="critical" <?php if(($_POST['severity'] ?? '') === 'critical') echo 'selected'; ?>>Critical</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group half-width">
                                <label for="location">Location</label>
                                <input type="text" name="location" id="location" placeholder="Optional: e.g., Building A, Main Gate" class="form-input" value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>">
                            </div>
                            <div class="form-group half-width">
                                <label for="image">Attach Photo (optional)</label>
                                <label class="file-upload">
                                    <input type="file" name="image" id="image" accept="image/*" class="form-file">
                                    <span class="file-upload-button">Choose file</span>
                                    <span class="file-upload-filename" id="image-filename">No file chosen</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="content">Description</label>
                            <textarea name="content" id="content" rows="6" placeholder="Describe what's happening, include steps taken or assistance needed." class="form-textarea" required><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-actions">
                            <span class="form-disclaimer">⚠️ All submissions are reviewed by administrators before going live.</span>
                            <button type="submit" class="btn btn-save">Submit Report</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', () => {
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

            button.classList.add('active');
            const targetTab = button.dataset.tab;
            const content = document.getElementById(`${targetTab}-tab`);
            if (content) {
                content.classList.add('active');
            }
        });
    });

    // Update filename display for custom file upload
    const imageInput = document.getElementById('image');
    const imageName = document.getElementById('image-filename');
    if (imageInput && imageName) {
        imageInput.addEventListener('change', (e) => {
            const f = e.target.files && e.target.files[0];
            imageName.textContent = f ? f.name : 'No file chosen';
        });
    }
</script>
</body>
</html>