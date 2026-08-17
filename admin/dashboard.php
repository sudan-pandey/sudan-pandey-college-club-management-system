<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Enforce strict administrator authorization
requireRole('admin');

try {
    // Collect broad summary metrics
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalClubs = $pdo->query("SELECT COUNT(*) FROM clubs")->fetchColumn();
    $totalMembers = $pdo->query("SELECT COUNT(*) FROM memberships WHERE status='active'")->fetchColumn();
    $totalEvents = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
    $totalTasks = $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
    $totalFeedback = $pdo->query("SELECT COUNT(*) FROM feedback")->fetchColumn();

    // Query recent activities / announcements
    $recentAnnouncements = $pdo->query("SELECT a.*, c.name AS club_name
                                        FROM announcements a
                                        JOIN clubs c ON a.club_id = c.id
                                        ORDER BY a.created_at DESC LIMIT 5")->fetchAll();
} catch (PDOException $e) {
    die("Query Error: " . htmlspecialchars($e->getMessage()));
}
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/navbar.php'; ?>

<div class="container dashboard-container">
    <!-- Admin Sidebar Navigation -->
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
            <li><a href="users.php">👥 Users / Roles</a></li>
            <li><a href="clubs.php">🏛️ Clubs</a></li>
            <li><a href="responsibilities.php">🎖️ Responsibilities</a></li>
            <li><a href="memberships.php">🤝 Memberships</a></li>
            <li><a href="events.php">📅 Events Directory</a></li>
            <li><a href="registrations.php">📝 Event Registrants</a></li>
            <li><a href="attendance.php">✓ Attendance Logs</a></li>
            <li><a href="announcements.php">📢 Announcements</a></li>
            <li><a href="feedback.php">⭐ Feedback & Ratings</a></li>
            <li><a href="tasks.php">✅ Task Assignments</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="flex-header">
            <h2>Administration Dashboard</h2>
            <span class="badge">System-Level Control</span>
        </div>

        <?php displayAlerts(); ?>

        <!-- Overall Summary Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <h5>Registered Users</h5>
                <div class="value"><?php echo $totalUsers; ?></div>
            </div>
            <div class="stat-card">
                <h5>Total Clubs</h5>
                <div class="value"><?php echo $totalClubs; ?></div>
            </div>
            <div class="stat-card">
                <h5>Active Members</h5>
                <div class="value"><?php echo $totalMembers; ?></div>
            </div>
            <div class="stat-card">
                <h5>Total Events</h5>
                <div class="value"><?php echo $totalEvents; ?></div>
            </div>
            <div class="stat-card">
                <h5>Assigned Tasks</h5>
                <div class="value"><?php echo $totalTasks; ?></div>
            </div>
            <div class="stat-card">
                <h5>Feedbacks</h5>
                <div class="value"><?php echo $totalFeedback; ?></div>
            </div>
        </div>

        <!-- Recent System Activity / Announcements -->
        <div class="card-grid" style="grid-template-columns: 1fr; margin-top: 20px;">
            <div class="feature-card">
                <h3>Latest Club Announcements</h3>
                <p>Global view of recent updates dispatched by club coordination teams.</p>

                <?php if (empty($recentAnnouncements)): ?>
                    <p class="text-muted" style="font-style: italic;">No announcements published yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Club</th>
                                    <th>Title</th>
                                    <th>Content</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentAnnouncements as $ann): ?>
                                    <tr>
                                        <td><strong><?php echo escape($ann['club_name']); ?></strong></td>
                                        <td><?php echo escape($ann['title']); ?></td>
                                        <td><?php echo escape(substr($ann['content'], 0, 75)) . (strlen($ann['content']) > 75 ? '...' : ''); ?></td>
                                        <td><?php echo escape($ann['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
