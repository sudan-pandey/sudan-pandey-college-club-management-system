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
$success = $_GET['success'] ?? '';

// Handle cancel/delete event actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $error = "CSRF verification failed.";
    } else {
        $eventId = intval($_POST['event_id'] ?? 0);

        // 1. Strict ownership check: Verify event belongs to THIS club head's club!
        $stmtCheck = $pdo->prepare("SELECT id FROM events WHERE id = ? AND club_id = ? LIMIT 1");
        $stmtCheck->execute([$eventId, $clubId]);

        if (!$stmtCheck->fetch()) {
            $error = "Security Error: You are not authorized to modify this event.";
        } else {
            $action = $_POST['action'];
            if ($action === 'cancel') {
                $stmtCancel = $pdo->prepare("UPDATE events SET status = 'cancelled' WHERE id = ?");
                $stmtCancel->execute([$eventId]);
                $success = "Event cancelled successfully.";
            } elseif ($action === 'delete') {
                $stmtDel = $pdo->prepare("DELETE FROM events WHERE id = ?");
                $stmtDel->execute([$eventId]);
                $success = "Event deleted successfully.";
            }
        }
    }
}

// Fetch all events organized by this club
try {
    $stmt = $pdo->prepare("SELECT e.*,
                             (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) AS reg_count
                           FROM events e
                           WHERE e.club_id = ?
                           ORDER BY e.event_date DESC");
    $stmt->execute([$clubId]);
    $events = $stmt->fetchAll();
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
            <li><a href="events.php" class="active">📅 Club Events</a></li>
            <li><a href="calendar.php">🗓️ Calendar View</a></li>
            <li><a href="registrations.php">📝 Event Registrations</a></li>
            <li><a href="attendance.php">✓ Mark Attendance</a></li>
            <li><a href="announcements.php">📢 Announcements</a></li>
            <li><a href="feedback.php">⭐ Feedback Reviews</a></li>
            <li><a href="tasks.php">✅ Task Coordination</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="flex-header">
            <h2>Manage Club Events</h2>
            <a href="create-event.php" class="btn btn-primary">+ Create New Event</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo escape($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo escape($success); ?></div>
        <?php endif; ?>

        <div class="card-grid">
            <?php if (empty($events)): ?>
                <p class="text-muted" style="font-style: italic;">No events have been posted for your club yet.</p>
            <?php else: ?>
                <?php foreach ($events as $event): ?>
                    <div class="card">
                        <div>
                            <span class="badge" style="margin-bottom: 10px;"><?php echo strtoupper(escape($event['status'])); ?></span>
                            <h3><?php echo escape($event['title']); ?></h3>
                            <p><?php echo escape($event['description']); ?></p>

                            <div style="font-size: 0.9rem; margin-bottom: 20px; color: var(--text-muted);">
                                <div>📍 <strong>Location:</strong> <?php echo escape($event['location']); ?></div>
                                <div>📅 <strong>Date:</strong> <?php echo escape($event['event_date']); ?></div>
                                <div>👥 <strong>Total Registrations:</strong> <?php echo escape($event['reg_count']); ?></div>
                            </div>
                        </div>

                        <div style="display: flex; gap: 10px; margin-top: auto;">
                            <a href="edit-event.php?id=<?php echo $event['id']; ?>" class="btn btn-outline btn-sm">Edit</a>

                            <?php if ($event['status'] === 'upcoming'): ?>
                                <form action="events.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this event?');">
                                    <?php csrfInput(); ?>
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                    <button type="submit" class="btn btn-warning btn-sm">Cancel Event</button>
                                </form>
                            <?php endif; ?>

                            <form action="events.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this event? This will also remove any related registrations and attendance logs.');">
                                <?php csrfInput(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
