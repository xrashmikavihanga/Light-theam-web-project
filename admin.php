<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'connection.php';

// 1. Session & Role Protection
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// 2. Action Handlers (Form Submissions)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Disable mysqli strict exceptions temporarily so missing column fallbacks don't crash PHP
    $driver = new mysqli_driver();
    $driver->report_mode = MYSQLI_REPORT_OFF;

    // Update Post Status
    if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        $postId = intval($_POST['postId']);
        $newStatus = $_POST['status'];
        
        $stmt = $conn->prepare("UPDATE posts SET status = ? WHERE postId = ?");
        if (!$stmt) {
            $stmt = $conn->prepare("UPDATE posts SET status = ? WHERE id = ?");
        }
        if (!$stmt) {
            $stmt = $conn->prepare("UPDATE posts SET status = ? WHERE post_id = ?");
        }

        if ($stmt) {
            $stmt->bind_param("si", $newStatus, $postId);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    // Direct Quick Approve
    if (isset($_POST['action']) && $_POST['action'] === 'approve_post') {
        $postId = intval($_POST['postId']);
        
        $stmt = $conn->prepare("UPDATE posts SET status = 'approved' WHERE postId = ?");
        if (!$stmt) {
            $stmt = $conn->prepare("UPDATE posts SET status = 'approved' WHERE id = ?");
        }
        if (!$stmt) {
            $stmt = $conn->prepare("UPDATE posts SET status = 'approved' WHERE post_id = ?");
        }

        if ($stmt) {
            $stmt->bind_param("i", $postId);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Delete Post
    if (isset($_POST['action']) && $_POST['action'] === 'delete_post') {
        $postId = intval($_POST['postId']);
        
        $stmt = $conn->prepare("DELETE FROM posts WHERE postId = ?");
        if (!$stmt) {
            $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
        }
        if (!$stmt) {
            $stmt = $conn->prepare("DELETE FROM posts WHERE post_id = ?");
        }

        if ($stmt) {
            $stmt->bind_param("i", $postId);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    // Delete User
    if (isset($_POST['action']) && $_POST['action'] === 'delete_user') {
        $userId = intval($_POST['userId']);
        $currentSessionUser = $_SESSION['user_id'] ?? $_SESSION['userId'] ?? 0;
        
        if ($userId != $currentSessionUser) {
            $stmt = $conn->prepare("DELETE FROM users WHERE userId = ?");
            if ($stmt) {
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
    
    header("Location: admin.php");
    exit();
}

// 3. Fetch Metrics safely
$totalUsers = 0;
$totalPosts = 0;
$pendingPosts = 0;
$approvedPosts = 0;

$res1 = $conn->query("SELECT COUNT(*) AS count FROM users");
if ($res1) $totalUsers = $res1->fetch_assoc()['count'] ?? 0;

$res2 = $conn->query("SELECT COUNT(*) AS count FROM posts");
if ($res2) $totalPosts = $res2->fetch_assoc()['count'] ?? 0;

$res3 = $conn->query("SELECT COUNT(*) AS count FROM posts WHERE status='pending'");
if ($res3) $pendingPosts = $res3->fetch_assoc()['count'] ?? 0;

$res4 = $conn->query("SELECT COUNT(*) AS count FROM posts WHERE status='approved'");
if ($res4) $approvedPosts = $res4->fetch_assoc()['count'] ?? 0;

// 4. Fetch Table Data
$postsResult = $conn->query("
    SELECT p.*, u.userName 
    FROM posts p 
    JOIN users u ON p.userId = u.userId 
    ORDER BY p.status DESC
");

if (!$postsResult) {
    // Secondary attempt without strict sorting if needed
    $postsResult = $conn->query("
        SELECT p.*, u.userName 
        FROM posts p 
        JOIN users u ON p.userId = u.userId
    ");
}

$usersResult = $conn->query("
    SELECT u.userId, u.userName, u.role, ud.fullName, ud.email, ud.contactNumber 
    FROM users u 
    LEFT JOIN userData ud ON u.userId = ud.userId 
    ORDER BY u.userId DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Control Panel</title>
    <link rel="stylesheet" href="./styles/style.css">
</head>
<body class="dashboard-body">

<div class="dashboard-wrapper">
    
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">🛡️</div>
            <div>
                <div class="brand-title">Disaster Management</div>
                <div class="brand-subtitle">Admin Control Center</div>
            </div>
        </div>
        <div class="user-profile">
            <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'] ?? $_SESSION['userName'] ?? 'A', 0, 1)); ?></div>
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? $_SESSION['userName'] ?? 'Admin'); ?></span>
                <a href="index.php" class="logout-link">Logout</a>
            </div>
        </div>
    </aside>

    <!-- Main Content Panel -->
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Admin Control Panel</h1>
            <p class="page-subtitle">Overview and management of active posts and registered users</p>
        </div>

        <!-- Metric Cards Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Total Users</div>
                <div class="stat-value"><?php echo $totalUsers; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Total Posts</div>
                <div class="stat-value"><?php echo $totalPosts; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Pending Review</div>
                <div class="stat-value" style="color: #f59e0b;"><?php echo $pendingPosts; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Approved / Live</div>
                <div class="stat-value" style="color: #10b981;"><?php echo $approvedPosts; ?></div>
            </div>
        </div>

        <div class="tab-panel">
            <div class="tab-buttons">
                <button class="tab-button active" data-tab="posts">Posts</button>
                <button class="tab-button" data-tab="users">Users</button>
            </div>

            <div class="tab-content active" id="posts-tab">
                <div class="content-card">
                    <div class="card-header">
                        <h3>Manage User Posts</h3>
                    </div>
                    <div class="table-container">
                        <table class="styled-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Author</th>
                                    <th>Title & Details</th>
                                    <th>Content</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($postsResult && $postsResult->num_rows > 0): ?>
                                    <?php while ($post = $postsResult->fetch_assoc()): ?>
                                        <?php $currentPostId = $post['id'] ?? $post['postId'] ?? $post['post_id'] ?? 0; ?>
                                        <tr>
                                            <td><span class="id-tag">#<?php echo $currentPostId; ?></span></td>
                                            <td><strong><?php echo htmlspecialchars($post['userName'] ?? 'User'); ?></strong></td>
                                            <td>
                                                <div><strong><?php echo htmlspecialchars($post['title'] ?? 'Untitled'); ?></strong></div>
                                                <?php if (!empty($post['location'])): ?>
                                                    <small style="color: #94a3b8;">📍 <?php echo htmlspecialchars($post['location']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($post['content'] ?? $post['description'] ?? ''); ?></td>
                                            <td>
                                                <span class="status-pill status-<?php echo strtolower($post['status'] ?? 'pending'); ?>">
                                                    <?php echo strtoupper($post['status'] ?? 'PENDING'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-flex">
                                                    <form action="admin.php" method="POST">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="postId" value="<?php echo $currentPostId; ?>">
                                                        <select name="status" class="styled-select">
                                                            <option value="pending" <?php if(($post['status'] ?? '') == 'pending') echo 'selected'; ?>>Pending</option>
                                                            <option value="approved" <?php if(($post['status'] ?? '') == 'approved') echo 'selected'; ?>>Approve</option>
                                                            <option value="resolved" <?php if(($post['status'] ?? '') == 'resolved') echo 'selected'; ?>>Resolve</option>
                                                            <option value="rejected" <?php if(($post['status'] ?? '') == 'rejected') echo 'selected'; ?>>Reject</option>
                                                        </select>
                                                        <button type="submit" class="btn btn-save">Save</button>
                                                    </form>

                                                    <?php if (strtolower($post['status'] ?? 'pending') === 'pending'): ?>
                                                        <form action="admin.php" method="POST">
                                                            <input type="hidden" name="action" value="approve_post">
                                                            <input type="hidden" name="postId" value="<?php echo $currentPostId; ?>">
                                                            <button type="submit" class="btn btn-save">Approve</button>
                                                        </form>
                                                    <?php endif; ?>

                                                    <form action="admin.php" method="POST" onsubmit="return confirm('Permanently delete this post?');">
                                                        <input type="hidden" name="action" value="delete_post">
                                                        <input type="hidden" name="postId" value="<?php echo $currentPostId; ?>">
                                                        <button type="submit" class="btn btn-danger">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="empty-state">No posts submitted yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-content" id="users-tab">
                <div class="content-card">
                    <div class="card-header">
                        <h3>Registered Users Directory</h3>
                    </div>
                    <div class="table-container">
                        <table class="styled-table">
                            <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Contact</th>
                                    <th>Role</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($usersResult && $usersResult->num_rows > 0): ?>
                                    <?php while ($user = $usersResult->fetch_assoc()): ?>
                                        <tr>
                                            <td><span class="id-tag">#<?php echo $user['userId']; ?></span></td>
                                            <td><strong><?php echo htmlspecialchars($user['userName']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($user['fullName'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($user['contactNumber'] ?? 'N/A'); ?></td>
                                            <td><span class="status-pill status-approved"><?php echo strtoupper($user['role']); ?></span></td>
                                            <td>
                                                <?php if ($user['role'] !== 'admin'): ?>
                                                    <form action="admin.php" method="POST" onsubmit="return confirm('Permanently remove this user account?');">
                                                        <input type="hidden" name="action" value="delete_user">
                                                        <input type="hidden" name="userId" value="<?php echo $user['userId']; ?>">
                                                        <button type="submit" class="btn btn-danger">Remove</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span style="color: #64748b; font-size: 12px;">🛡️ System Admin</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="empty-state">No registered users found in the system.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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
            const target = button.dataset.tab;
            const content = document.getElementById(`${target}-tab`);
            if (content) {
                content.classList.add('active');
            }
        });
    });
</script>

</body>
</html>