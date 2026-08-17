<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('club_head');

$userId = $_SESSION['user_id'];

try {
    // Identify own club
    $club = getOwnClub($pdo, $userId);

    $totalMembers = 0;
    $upcomingEvents = 0;
    $pendingTasks = 0;
    $inProgressTasks = 0;
    $completedTasks = 0;
    $overdueTasks = 0;
    $recentFeedback = [];

    if ($club) {
        $clubId = $club['id'];

        // 1. Total active members in their own club
        $stmtMemb = $pdo->prepare("SELECT COUNT(*) FROM memberships WHERE club_id = ? AND status = 'active'");
        $stmtMemb->execute([$clubId]);
        $totalMembers = $stmtMemb->fetchColumn();

        // 2. Upcoming events count in their own club
        $stmtEv = $pdo->prepare("SELECT COUNT(*) FROM events WHERE club_id = ? AND status = 'upcoming'");
        $stmtEv->execute([$clubId]);
        $upcomingEvents = $stmtEv->fetchColumn();

        // 3. Task counts inside own club
        $stmtTasksCount = $pdo->prepare("SELECT status, deadline FROM tasks WHERE club_id = ?");
        $stmtTasksCount->execute([$clubId]);
        $clubTasksList = $stmtTasksCount->fetchAll();

        foreach ($clubTasksList as $t) {
            if ($t['status'] === 'pending') {
                $pendingTasks++;
            } elseif ($t['status'] === 'in_progress') {
                $inProgressTasks++;
            } elseif ($t['status'] === 'completed') {
                $completedTasks++;
            }
            if (isTaskOverdue($t['deadline'], $t['status'])) {
                $overdueTasks++;
            }
        }

        // 4. Recent feedback on own club's events
        $stmtFb = $pdo->prepare("SELECT f.*, e.title AS event_title, u.full_name AS student_name
                                 FROM feedback f
                                 JOIN events e ON f.event_id = e.id
                                 JOIN users u ON f.user_id = u.id
                                 WHERE e.club_id = ?
                                 ORDER BY f.submitted_at DESC LIMIT 5");
        $stmtFb->execute([$clubId]);
        $recentFeedback = $stmtFb->fetchAll();
    }
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
            <?php if ($club): ?>
                <li><a href="club.php">🏛️ Club Details</a></li>
                <li><a href="members.php">👥 Members List</a></li>
                <li><a href="events.php">📅 Club Events</a></li>
            <li><a href="calendar.php">🗓️ Calendar View</a></li>
                <li><a href="registrations.php">📝 Event Registrations</a></li>
                <li><a href="attendance.php">✓ Mark Attendance</a></li>
                <li><a href="announcements.php">📢 Announcements</a></li>
                <li><a href="feedback.php">⭐ Feedback Reviews</a></li>
                <li><a href="tasks.php">✅ Task Coordination</a></li>
            <?php endif; ?>
        </ul>
    </aside>

    <main class="main-content">
        <div class="flex-header">
            <h2>Club Leader Workspace</h2>
            <span class="badge">Club Head Role</span>
        </div>

        <?php displayAlerts(); ?>

        <?php if (!$club): ?>
            <div class="feature-card" style="border-left: 5px solid var(--danger);">
                <h3>🏛️ No Club Assignment</h3>
                <p>You have successfully registered or been promoted to a Club Head! However, the College Admin has not assigned you to head a specific club yet. Please reach out to the Administrator to configure your leadership duties.</p>
            </div>
        <?php else: ?>
            <div class="feature-card" style="border-left: 5px solid var(--primary-color); margin-bottom: 30px;">
                <h3>🏛️ Heading: <?php echo escape($club['name']); ?></h3>
                <p><?php echo escape($club['description']); ?></p>
            </div>

            <!-- Dashboard Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h5>Total Members</h5>
                    <div class="value"><?php echo $totalMembers; ?></div>
                </div>
                <div class="stat-card">
                    <h5>Upcoming Events</h5>
                    <div class="value"><?php echo $upcomingEvents; ?></div>
                </div>
                <div class="stat-card">
                    <h5>Pending Tasks</h5>
                    <div class="value"><?php echo $pendingTasks; ?></div>
                </div>
                <div class="stat-card">
                    <h5>In Progress</h5>
                    <div class="value"><?php echo $inProgressTasks; ?></div>
                </div>
                <div class="stat-card">
                    <h5>Completed</h5>
                    <div class="value"><?php echo $completedTasks; ?></div>
                </div>
                <div class="stat-card">
                    <h5>Overdue Tasks</h5>
                    <div class="value" style="color: var(--danger);"><?php echo $overdueTasks; ?></div>
                </div>
            </div>

            <!-- Recent Feedback Updates -->
            <div class="card-grid" style="grid-template-columns: 1fr; margin-top: 25px;">
                <div class="feature-card">
                    <h3>Recent Event Feedbacks</h3>
                    <p class="text-muted" style="margin-bottom: 15px;">Live reviews sent by students after heading workshops or training sessions.</p>

                    <?php if (empty($recentFeedback)): ?>
                        <p class="text-muted" style="font-style: italic;">No feedback reviews logged yet for your events.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Event Title</th>
                                        <th>Rating Stars</th>
                                        <th>Comments</th>
                                        <th>Submitted At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentFeedback as $fb): ?>
                                        <tr>
                                            <td><strong><?php echo escape($fb['student_name']); ?></strong></td>
                                            <td><?php echo escape($fb['event_title']); ?></td>
                                            <td>
                                                <span class="stars-display">
                                                    <?php echo str_repeat('★', $fb['rating']) . str_repeat('☆', 5 - $fb['rating']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo escape($fb['comments']); ?></td>
                                            <td><?php echo escape($fb['submitted_at']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
