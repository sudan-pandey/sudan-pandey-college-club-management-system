<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('admin');

try {
    $stmt = $pdo->query("SELECT f.*, e.title AS event_title, u.full_name AS student_name, c.name AS club_name
                           FROM feedback f
                           JOIN events e ON f.event_id = e.id
                           JOIN users u ON f.user_id = u.id
                           JOIN clubs c ON e.club_id = c.id
                           ORDER BY f.submitted_at DESC");
    $feedbacks = $stmt->fetchAll();
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
            <li><a href="announcements.php">📢 Announcements</a></li>
            <li><a href="feedback.php" class="active">⭐ Feedback & Ratings</a></li>
            <li><a href="tasks.php">✅ Task Assignments</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2>Global Feedback & Ratings Panel</h2>
        <p class="text-muted">Explore average student ratings and constructive reviews on events.</p>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student</th>
                        <th>Event</th>
                        <th>Host Club</th>
                        <th>Rating</th>
                        <th>Written Review</th>
                        <th>Submitted At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($feedbacks)): ?>
                        <tr>
                            <td colspan="7" class="text-muted" style="text-align: center; font-style: italic;">No feedback registered.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($feedbacks as $fb): ?>
                            <tr>
                                <td><?php echo escape($fb['id']); ?></td>
                                <td><strong><?php echo escape($fb['student_name']); ?></strong></td>
                                <td><?php echo escape($fb['event_title']); ?></td>
                                <td><?php echo escape($fb['club_name']); ?></td>
                                <td>
                                    <span class="stars-display">
                                        <?php echo str_repeat('★', $fb['rating']) . str_repeat('☆', 5 - $fb['rating']); ?>
                                    </span>
                                    (<?php echo escape($fb['rating']); ?>/5)
                                </td>
                                <td><?php echo escape($fb['comments']); ?></td>
                                <td><?php echo escape($fb['submitted_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
