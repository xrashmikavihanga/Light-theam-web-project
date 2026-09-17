<?php
session_start();
require_once 'connection.php';

// Form Submissions (Update Status / Delete Post / Delete User)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Post Status Update 
    if ($action === 'update_status') {
        $postId = intval($_POST['postId']);
        $newStatus = $_POST['status'];
        
        $stmt = $conn->prepare("UPDATE posts SET status = ? WHERE postId = ?");
        $stmt->bind_param("si", $newStatus, $postId);
        $stmt->execute();
        $stmt->close();
    }

    // 2. Delete a Post
    if ($action === 'delete_post') {
        $postId = intval($_POST['postId']);
        
        $stmt = $conn->prepare("DELETE FROM posts WHERE postId = ?");
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $stmt->close();
    }

    // 3. Delete a User
    if ($action === 'delete_user') {
        $userId = intval($_POST['userId']);
        $currentAdminId = $_SESSION['userId'] ?? 0;
        
        // Admin cant delete the admin
        if ($userId != $currentAdminId) {
            $stmt = $conn->prepare("DELETE FROM users WHERE userId = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    header("Location: admin.php");
    exit();
}

// Count status
$totalUsers = $conn->query("SELECT COUNT(*) AS count FROM users")->fetch_assoc()['count'] ?? 0;
$totalPosts = $conn->query("SELECT COUNT(*) AS count FROM posts")->fetch_assoc()['count'] ?? 0;
$pendingPosts = $conn->query("SELECT COUNT(*) AS count FROM posts WHERE status='pending'")->fetch_assoc()['count'] ?? 0;
$approvedPosts = $conn->query("SELECT COUNT(*) AS count FROM posts WHERE status='approved'")->fetch_assoc()['count'] ?? 0;

// Posts Table එකට දත්ත ගැනීම
$postsResult = $conn->query("
    SELECT p.*, u.userName 
    FROM posts p 
    JOIN users u ON p.userId = u.userId 
    ORDER BY p.postId DESC
");

// Users Table එකට දත්ත ගැනීම
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
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="./styles/style.css">
</head>
<body class="dashboard-body admin-body">

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
            <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['userName'] ?? 'A', 0, 1)); ?></div>
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['userName'] ?? 'Admin'); ?></span>
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

            <!-- Posts Tab -->
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
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Post table render -->
                                <?php if ($postsResult && $postsResult->num_rows > 0): ?>
                                    <?php while ($post = $postsResult->fetch_assoc()): ?>
                                        <tr>
                                            <td><span class="id-tag">#<?php echo $post['postId']; ?></span></td>
                                            <td><strong><?php echo htmlspecialchars($post['userName']); ?></strong></td>
                                            <td>
                                                <div><strong><?php echo htmlspecialchars($post['title']); ?></strong></div>
                                                <?php if (!empty($post['location'])): ?>
                                                    <small style="color: #94a3b8;">📍 <?php echo htmlspecialchars($post['location']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($post['description']); ?></td>
                                            <td>
                                                <span class="status-pill status-<?php echo strtolower($post['status']); ?>">
                                                    <?php echo strtoupper($post['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-flex">
                                                    <!-- Status Update Form -->
                                                    <form action="admin.php" method="POST">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="postId" value="<?php echo $post['postId']; ?>">
                                                        <select name="status" class="styled-select">
                                                            <option value="pending" <?php if($post['status'] == 'pending') echo 'selected'; ?>>Pending</option>
                                                            <option value="approved" <?php if($post['status'] == 'approved') echo 'selected'; ?>>Approve</option>
                                                            
                                                        </select>
                                                        <button type="submit" class="btn btn-save">Save</button>
                                                    </form>

                                                    <!-- Delete Post Form -->
                                                    <form action="admin.php" method="POST" onsubmit="return confirm('Permanently delete this post?');">
                                                        <input type="hidden" name="action" value="delete_post">
                                                        <input type="hidden" name="postId" value="<?php echo $post['postId']; ?>">
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

            <!-- Users Tab -->
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
                                                    <!-- Delete User Form -->
                                                    <form action="admin.php" method="POST" onsubmit="return confirm('Permanently remove this user account?');">
                                                        <input type="hidden" name="action" value="delete_user">
                                                        <input type="hidden" name="userId" value="<?php echo $user['userId']; ?>">
                                                        <button type="submit" class="btn btn-danger">Remove</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span style="color: #64748b; font-size: 12px;">System Admin</span>
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
    // Simple Tab Switching Script
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