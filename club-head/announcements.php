<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

requireRole('club_head');

$userId = $_SESSION['user_id'];
$club = getOwnClub($pdo, $userId);

if (!$club) {
    header("Location: dashboard.php?error=" . urlencode("No club assignment yet."));
    exit;
}

$clubId = $club['id'];
$error = '';
$success = '';

// Handle posting / deleting announcements
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $error = "CSRF verification failed.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $title = trim($_POST['title'] ?? '');
            $priority = trim($_POST['priority'] ?? 'General');
            $content = trim($_POST['content'] ?? '');

            if (empty($title) || empty($content)) {
                $error = "Title and content cannot be blank.";
            } else {
                try {
                    $ins = $pdo->prepare("INSERT INTO announcements (club_id, title, priority, content, created_by) VALUES (?, ?, ?, ?, ?)");
                    $ins->execute([$clubId, $title, $priority, $content, $userId]);
                    $success = "Announcement published successfully!";
                } catch (PDOException $e) {
                    try {
                        $ins = $pdo->prepare("INSERT INTO announcements (club_id, title, content, created_by) VALUES (?, ?, ?, ?)");
                        $ins->execute([$clubId, $title, $content, $userId]);
                        $success = "Announcement published successfully!";
                    } catch (PDOException $ex) {
                        $error = "Database Error: " . $ex->getMessage();
                    }
                }
            }
        } elseif ($action === 'delete') {
            $annId = intval($_POST['announcement_id'] ?? 0);
            if ($annId > 0) {
                // Verify ownership
                $chk = $pdo->prepare("SELECT id FROM announcements WHERE id = ? AND club_id = ? LIMIT 1");
                $chk->execute([$annId, $clubId]);
                if ($chk->fetch()) {
                    $del = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
                    $del->execute([$annId]);
                    $success = "Announcement deleted successfully.";
                } else {
                    $error = "Security Error: Unauthorized operation.";
                }
            }
        }
    }
}

// Fetch centralized announcements from ALL clubs and system admin
try {
    $stmt = $pdo->query("SELECT a.*,
                                COALESCE(c.name, 'System Admin') AS sender_name,
                                u.full_name AS publisher
                         FROM announcements a
                         LEFT JOIN clubs c ON a.club_id = c.id
                         LEFT JOIN users u ON a.created_by = u.id
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
            <li><a href="club.php">🏛️ Club Details</a></li>
            <li><a href="members.php">👥 Members List</a></li>
            <li><a href="events.php">📅 Club Events</a></li>
            <li><a href="calendar.php">🗓️ Calendar View</a></li>
            <li><a href="registrations.php">📝 Event Registrations</a></li>
            <li><a href="attendance.php">✓ Mark Attendance</a></li>
            <li><a href="announcements.php" class="active">📢 Announcements</a></li>
            <li><a href="feedback.php">⭐ Feedback Reviews</a></li>
            <li><a href="tasks.php">✅ Task Coordination</a></li>
            <li><a href="profile.php">👤 Profile Settings</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2>Club Announcements</h2>
        <p class="text-muted">Broadcast updates, agendas, or alerts directly to your club members' dashboards.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo escape($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo escape($success); ?></div>
        <?php endif; ?>

        <!-- Create Announcement Form -->
        <div class="feature-card" style="margin-top: 20px; margin-bottom: 30px;">
            <h3>Broadcast New Announcement</h3>
            <form action="announcements.php" method="POST" style="margin-top: 15px;">
                <?php csrfInput(); ?>
                <input type="hidden" name="action" value="create">

                <div class="form-group">
                    <label for="title">Announcement Title</label>
                    <input type="text" id="title" name="title" class="form-control" placeholder="e.g. Mandatory Stand-up Meeting tomorrow" required>
                </div>

                <div class="form-group">
                    <label for="priority">Priority / Notice Tag</label>
                    <select id="priority" name="priority" class="form-control">
                        <option value="General">General</option>
                        <option value="Urgent">Urgent</option>
                        <option value="Event">Event</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="content">Detailed Content</label>
                    <textarea id="content" name="content" class="form-control" rows="4" placeholder="Enter full announcement message..." required></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Publish Announcement</button>
            </form>
        </div>

        <h3>Centralized All-Clubs Announcements Board</h3>
        <div style="margin-top: 20px;">
            <?php if (empty($announcements)): ?>
                <p class="text-muted" style="font-style: italic;">No announcements published yet.</p>
            <?php else: ?>
                <div class="card-grid" style="grid-template-columns: 1fr; gap: 20px;">
                    <?php foreach ($announcements as $ann): ?>
                        <?php
                            $priority = !empty($ann['priority']) ? $ann['priority'] : 'General';
                            $badgeColor = 'var(--primary-color)';
                            if ($priority === 'Urgent') {
                                $badgeColor = 'var(--danger)';
                            } elseif ($priority === 'Event') {
                                $badgeColor = 'var(--info)';
                            }
                            $sender = !empty($ann['sender_name']) ? $ann['sender_name'] : 'System Admin';
                        ?>
                        <div class="feature-card" style="border-left: 4px solid <?php echo $badgeColor; ?>;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span class="status-badge" style="background-color: var(--border-color); color: var(--text-main); padding: 4px 10px; font-weight: 600;">
                                        🏛️ <?php echo escape($sender); ?>
                                    </span>
                                    <span class="status-badge" style="background-color: <?php echo $badgeColor; ?>; color: #fff; padding: 4px 10px; font-weight: 600;">
                                        📌 <?php echo escape($priority); ?>
                                    </span>
                                </div>
                                <span class="text-muted" style="font-size: 0.85rem;">🕒 <?php echo escape($ann['created_at']); ?></span>
                            </div>

                            <h4 style="margin-bottom: 10px; color: var(--text-main); font-size: 1.25rem;"><?php echo escape($ann['title']); ?></h4>
                            <p style="color: var(--text-main); margin-bottom: 15px; line-height: 1.6; white-space: pre-wrap;"><?php echo escape($ann['content']); ?></p>

                            <div style="border-top: 1px dashed var(--border-color); padding-top: 10px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                                <span class="text-muted" style="font-size: 0.85rem;">Posted by: <strong><?php echo escape($ann['publisher'] ?: 'System Admin'); ?></strong></span>

                                <?php if (intval($ann['club_id']) === intval($clubId)): ?>
                                    <form action="announcements.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                                        <?php csrfInput(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="announcement_id" value="<?php echo $ann['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
