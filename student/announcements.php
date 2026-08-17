<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

requireRole('student');

$userId = $_SESSION['user_id'];

try {
    $membership = getActiveMembership($pdo, $userId);

    // Centralized Announcements: Fetch announcements from ALL clubs
    $stmt = $pdo->query("SELECT a.*, c.name AS club_name, u.full_name AS publisher
                         FROM announcements a
                         JOIN clubs c ON a.club_id = c.id
                         JOIN users u ON a.created_by = u.id
                         ORDER BY a.created_at DESC");
    $announcements = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/navbar.php'; ?>

<div class="container dashboard-container">
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php">📊 Dashboard</a></li>
            <li><a href="clubs.php">🏛️ Join Club</a></li>
            <?php if ($membership): ?>
                <li><a href="my-club.php">🎖️ My Club</a></li>
                <li><a href="tasks.php">✅ My Tasks</a></li>
            <?php endif; ?>
            <li><a href="announcements.php" class="active">📢 Announcements</a></li>
            <li><a href="events.php">📅 Events</a></li>
            <li><a href="calendar.php">🗓️ Calendar View</a></li>
            <li><a href="profile.php">👤 Profile Settings</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2>Centralized Announcements Board</h2>
        <p class="text-muted">Stay informed with updates and broadcasts from all student clubs across the college.</p>

        <div style="margin-top: 25px;">
            <?php if (empty($announcements)): ?>
                <p class="text-muted" style="font-style: italic;">No announcements have been published yet.</p>
            <?php else: ?>
                <?php foreach ($announcements as $ann): ?>
                    <div class="feature-card" style="margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; flex-wrap: wrap; gap: 10px;">
                            <span class="status-badge" style="background-color: var(--primary, #2c3e50); color: #fff; padding: 4px 10px; font-weight: 600;">
                                🏛️ <?php echo escape($ann['club_name']); ?>
                            </span>
                            <span class="text-muted" style="font-size: 0.85rem;">🕒 Published: <?php echo escape($ann['created_at']); ?></span>
                        </div>
                        <h3 style="margin-bottom: 10px; color: var(--primary, #2c3e50);"><?php echo escape($ann['title']); ?></h3>
                        <p style="color: var(--text-main); margin-bottom: 15px; line-height: 1.6;"><?php echo nl2br(escape($ann['content'])); ?></p>
                        <div style="font-size: 0.85rem; border-top: 1px dashed var(--border-color); padding-top: 10px; color: var(--text-muted);">
                            Published By: <strong><?php echo escape($ann['publisher']); ?></strong> (Club Head)
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
