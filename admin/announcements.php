<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('admin');

try {
    $stmt = $pdo->query("SELECT a.*, c.name AS club_name, u.full_name AS creator_name
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
            <li><a href="users.php">👥 Users / Roles</a></li>
            <li><a href="clubs.php">🏛️ Clubs</a></li>
            <li><a href="responsibilities.php">🎖️ Responsibilities</a></li>
            <li><a href="memberships.php">🤝 Memberships</a></li>
            <li><a href="events.php">📅 Events Directory</a></li>
            <li><a href="calendar.php">🗓️ Calendar View</a></li>
            <li><a href="registrations.php">📝 Event Registrants</a></li>
            <li><a href="attendance.php">✓ Attendance Logs</a></li>
            <li><a href="announcements.php" class="active">📢 Announcements</a></li>
            <li><a href="feedback.php">⭐ Feedback & Ratings</a></li>
            <li><a href="tasks.php">✅ Task Assignments</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2>Global Announcements Board</h2>
        <p class="text-muted">Broadcast logs published by various heads to keep club members up to date.</p>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Club</th>
                        <th>Announcement Title</th>
                        <th>Content Preview</th>
                        <th>Published By</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($announcements)): ?>
                        <tr>
                            <td colspan="6" class="text-muted" style="text-align: center; font-style: italic;">No announcements published.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($announcements as $ann): ?>
                            <tr>
                                <td><?php echo escape($ann['id']); ?></td>
                                <td><strong><?php echo escape($ann['club_name']); ?></strong></td>
                                <td><?php echo escape($ann['title']); ?></td>
                                <td><?php echo escape(substr($ann['content'], 0, 100)) . (strlen($ann['content']) > 100 ? '...' : ''); ?></td>
                                <td><?php echo escape($ann['creator_name']); ?></td>
                                <td><?php echo escape($ann['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
