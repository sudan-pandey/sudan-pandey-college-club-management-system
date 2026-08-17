<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('student');

$userId = $_SESSION['user_id'];

try {
    // Check if student belongs to a club
    $membership = getActiveMembership($pdo, $userId);

    // Get simple counts of upcoming events they can attend
    $upcomingEventsCount = $pdo->query("SELECT COUNT(*) FROM events WHERE event_date >= NOW() AND status='upcoming'")->fetchColumn();

    // Counts of assigned tasks to current user
    $stmtPending = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'pending'");
    $stmtPending->execute([$userId]);
    $pendingTasksCount = $stmtPending->fetchColumn();

    $stmtProgress = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'in_progress'");
    $stmtProgress->execute([$userId]);
    $progressTasksCount = $stmtProgress->fetchColumn();

    $stmtCompleted = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'completed'");
    $stmtCompleted->execute([$userId]);
    $completedTasksCount = $stmtCompleted->fetchColumn();

} catch (PDOException $e) {
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/navbar.php'; ?>

<div class="container dashboard-container">
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
            <li><a href="clubs.php">🏛️ Join Club</a></li>
            <?php if ($membership): ?>
                <li><a href="my-club.php">🎖️ My Club</a></li>
                <li><a href="tasks.php">✅ My Tasks</a></li>
                <li><a href="announcements.php">📢 Announcements</a></li>
            <?php endif; ?>
            <li><a href="events.php">📅 Events</a></li>
            <li><a href="calendar.php">🗓️ Calendar View</a></li>
            <li><a href="profile.php">👤 Profile Settings</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="flex-header">
            <h2>Student Dashboard</h2>
            <span class="badge">Student Hub</span>
        </div>

        <?php displayAlerts(); ?>

        <!-- Active Club Membership banner -->
        <?php if ($membership): ?>
            <div class="feature-card" style="border-left: 5px solid var(--success); margin-bottom: 35px;">
                <h3>🎉 Welcome Member of <?php echo escape($membership['club_name']); ?>!</h3>
                <p>Your current responsibility: <strong><?php echo $membership['responsibility_name'] ? escape($membership['responsibility_name']) : 'General Member'; ?></strong></p>
                <div style="margin-top: 15px;">
                    <a href="my-club.php" class="btn btn-primary btn-sm">View Club Directory</a>
                </div>
            </div>

            <!-- Student Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h5>Pending Tasks</h5>
                    <div class="value"><?php echo $pendingTasksCount; ?></div>
                </div>
                <div class="stat-card">
                    <h5>In Progress Tasks</h5>
                    <div class="value"><?php echo $progressTasksCount; ?></div>
                </div>
                <div class="stat-card">
                    <h5>Completed Tasks</h5>
                    <div class="value"><?php echo $completedTasksCount; ?></div>
                </div>
                <div class="stat-card">
                    <h5>Upcoming Events</h5>
                    <div class="value"><?php echo $upcomingEventsCount; ?></div>
                </div>
            </div>
        <?php else: ?>
            <div class="feature-card" style="border-left: 5px solid var(--warning); margin-bottom: 35px;">
                <h3>🏛️ You are not in any Club yet!</h3>
                <p>Students must belong to a club to receive tasks, view member discussions, and participate in club management. You can only join one active club at a time.</p>
                <div style="margin-top: 15px;">
                    <a href="clubs.php" class="btn btn-warning btn-sm">Browse & Join Clubs</a>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
