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
    <aside class="sidebar" aria-label="Sidebar Navigation">
        <nav>
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php">
                        <svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="club.php">
                        <svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18"></path><path d="M5 21V7l8-4v18"></path><path d="M19 21V11l-6-3"></path><path d="M9 9l0 .01"></path><path d="M9 12l0 .01"></path><path d="M9 15l0 .01"></path><path d="M9 18l0 .01"></path></svg>
                        <span>Club Details</span>
                    </a>
                </li>
                <li>
                    <a href="members.php">
                        <svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        <span>Members List</span>
                    </a>
                </li>
                <li>
                    <a href="events.php" class="active" aria-current="page">
                        <svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        <span>Club Events</span>
                    </a>
                </li>
                <li>
                    <a href="calendar.php">
                        <svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line><path d="M8 14h.01"></path><path d="M12 14h.01"></path><path d="M16 14h.01"></path><path d="M8 18h.01"></path><path d="M12 18h.01"></path></svg>
                        <span>Calendar View</span>
                    </a>
                </li>
                <li>
                    <a href="registrations.php">
                        <svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        <span>Event Registrations</span>
                    </a>
                </li>
                <li>
                    <a href="attendance.php">
                        <svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        <span>Mark Attendance</span>
                    </a>
                </li>
                <li>
                    <a href="announcements.php">
                        <svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                        <span>Announcements</span>
                    </a>
                </li>
                <li>
                    <a href="feedback.php">
                        <svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        <span>Feedback Reviews</span>
                    </a>
                </li>
                <li>
                    <a href="tasks.php">
                        <svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                        <span>Task Coordination</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <div class="flex-header">
            <div>
                <h2>Manage Club Events</h2>
                <p class="subtitle">View, create, and manage upcoming and past club events.</p>
            </div>
            <a href="create-event.php" class="btn btn-primary">+ Create New Event</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo escape($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo escape($success); ?></div>
        <?php endif; ?>

        <?php if (empty($events)): ?>
            <div class="empty-state-card">
                <div class="empty-state-icon-wrapper">
                    <svg class="empty-state-icon" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                        <line x1="10" y1="14" x2="14" y2="14"></line>
                        <line x1="12" y1="12" x2="12" y2="16"></line>
                    </svg>
                </div>
                <h3 class="empty-state-headline">No events scheduled</h3>
                <p class="empty-state-description">
                    No events have been posted for your club yet. Get started by organizing your first club workshop, meeting, or activity.
                </p>
                <a href="create-event.php" class="btn btn-primary">+ Create New Event</a>
            </div>
        <?php else: ?>
            <div class="card-grid">
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
