<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('admin');

try {
    $stmt = $pdo->query("SELECT r.*, e.title AS event_title, u.full_name AS student_name, u.email AS student_email, c.name AS club_name
                           FROM registrations r
                           JOIN events e ON r.event_id = e.id
                           JOIN users u ON r.user_id = u.id
                           JOIN clubs c ON e.club_id = c.id
                           ORDER BY r.registered_at DESC");
    $registrations = $stmt->fetchAll();
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
            <li><a href="registrations.php" class="active">📝 Event Registrants</a></li>
            <li><a href="attendance.php">✓ Attendance Logs</a></li>
            <li><a href="announcements.php">📢 Announcements</a></li>
            <li><a href="feedback.php">⭐ Feedback & Ratings</a></li>
            <li><a href="tasks.php">✅ Task Assignments</a></li>
            <li><a href="profile.php">👤 Profile Settings</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2>Global Event Registrants Log</h2>
        <p class="text-muted">Live audit of student sign-ups for all published club events.</p>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student</th>
                        <th>Email</th>
                        <th>Event Title</th>
                        <th>Organized By</th>
                        <th>Registered At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($registrations)): ?>
                        <tr>
                            <td colspan="6" class="text-muted" style="text-align: center; font-style: italic;">No student registrations logged yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($registrations as $reg): ?>
                            <tr>
                                <td><?php echo escape($reg['id']); ?></td>
                                <td><strong><?php echo escape($reg['student_name']); ?></strong></td>
                                <td><?php echo escape($reg['student_email']); ?></td>
                                <td><?php echo escape($reg['event_title']); ?></td>
                                <td><?php echo escape($reg['club_name']); ?></td>
                                <td><?php echo escape($reg['registered_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
