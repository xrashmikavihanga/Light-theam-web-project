<?php
session_start();

// Simple access check to show the page only to logged users
if (!isset($_SESSION['userId'])) {
    header('Location: index.php');
    exit();
}

$teamMembers = [
    [
        
        'name' => 'Rashmika Vihanga',
        'role' => 'Backend Developer / Project Lead',
        'bio' => 'Designed the database model, authentication flow, and secure form handling logic.Coordinated the team, defined the feature set, and ensured the application meets the project goals.',
        'email' => 'xrashmikavihanga@gmail.com',
        'phone' => '+77 8063 588',
        'initial' => 'A',
        'photo' => './images/IMG_2652.jpg',
    ],
    [
        'name' => 'Rashmika Vihanga',
        'role' => 'Backend Developer',
        'bio' => 'Designed the database model, authentication flow, and secure form handling logic.',
        'email' => 'xrashmikavihanga@gmail.com',
        'phone' => '+77 8063 588',
        'initial' => 'B',
        'photo' => '',
    ],
    [
        'name' => 'Chathu Silva',
        'role' => 'Project Lead',
        'bio' => 'Coordinated the team, defined the feature set, and ensured the application meets the project goals.',
        'email' => 'chathu.silva@example.com',
        'phone' => '+94 70 345 6789',
        'initial' => 'C',
        'photo' => '',
    ],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact & Team Information</title>
    <link rel="stylesheet" href="./styles/style.css?v=<?php echo time(); ?>">
</head>
<body class="dashboard-body">

<div class="dashboard-wrapper">
    <aside class="sidebar">
        <div class="brand">
            <span>🛠️ Contact Team</span>
            <small class="brand-subtitle">Group 21 | Disaster Management Project</small>
        </div>

        <div class="user-profile">
            <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['userName'] ?? 'U', 0, 1)); ?></div>
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['userName'] ?? 'User'); ?></span>
                <a href="homepage.php" class="logout-link">Back to Homepage</a>
                <a href="logout.php" class="sidebar-link">Logout</a>
            </div>
        </div>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Contact & Developer Info</h1>
            <p class="page-subtitle">Meet the Group 21 team behind the Disaster Management project.</p>
        </div>

        <section class="contact-panel">
            <div class="card-header">
                <h3>Project Team</h3>
                <p class="panel-description">Here are the members responsible for the app design, backend, and delivery.</p>
            </div>

            <div class="team-grid">
                <?php foreach ($teamMembers as $member): ?>
                    <div class="team-card">
                        <div class="developer-avatar">
                            <?php if (!empty($member['photo'])): ?>
                                <img src="<?php echo htmlspecialchars($member['photo']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?> photo">
                            <?php else: ?>
                                <?php echo htmlspecialchars($member['initial']); ?>
                            <?php endif; ?>
                        </div>
                        <h4><?php echo htmlspecialchars($member['name']); ?></h4>
                        <span class="status-pill status-approved"><?php echo htmlspecialchars($member['role']); ?></span>
                        <p><?php echo htmlspecialchars($member['bio']); ?></p>
                        <div class="contact-details">
                            <div class="contact-item"><strong>Email:</strong> <?php echo htmlspecialchars($member['email']); ?></div>
                            <div class="contact-item"><strong>Phone:</strong> <?php echo htmlspecialchars($member['phone']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</div>

</body>
</html>
