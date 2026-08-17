<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

requireRole('admin');

$error = '';
$clubId = intval($_GET['club_id'] ?? 0);

if ($clubId <= 0) {
    header("Location: clubs.php");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM clubs WHERE id = ? LIMIT 1");
    $stmt->execute([$clubId]);
    $club = $stmt->fetch();

    if (!$club) {
        header("Location: clubs.php?error=" . urlencode("Club not found."));
        exit;
    }
} catch (PDOException $e) {
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $error = "CSRF verification failed.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) {
            $error = "Club name cannot be blank.";
        } else {
            try {
                // Check name uniqueness excluding current
                $uStmt = $pdo->prepare("SELECT id FROM clubs WHERE name = ? AND id != ? LIMIT 1");
                $uStmt->execute([$name, $clubId]);
                if ($uStmt->fetch()) {
                    $error = "A club with this name already exists.";
                } else {
                    $updateStmt = $pdo->prepare("UPDATE clubs SET name = ?, description = ? WHERE id = ?");
                    $updateStmt->execute([$name, $description, $clubId]);
                    header("Location: clubs.php?success=" . urlencode("Club updated successfully!"));
                    exit;
                }
            } catch (PDOException $e) {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/navbar.php'; ?>

<div class="container dashboard-container">
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php">📊 Dashboard</a></li>
            <li><a href="users.php">👥 Users / Roles</a></li>
            <li><a href="clubs.php" class="active">🏛️ Clubs</a></li>
            <li><a href="responsibilities.php">🎖️ Responsibilities</a></li>
            <li><a href="memberships.php">🤝 Memberships</a></li>
            <li><a href="events.php">📅 Events Directory</a></li>
            <li><a href="calendar.php">🗓️ Calendar View</a></li>
            <li><a href="registrations.php">📝 Event Registrants</a></li>
            <li><a href="attendance.php">✓ Attendance Logs</a></li>
            <li><a href="announcements.php">📢 Announcements</a></li>
            <li><a href="feedback.php">⭐ Feedback & Ratings</a></li>
            <li><a href="tasks.php">✅ Task Assignments</a></li>
        </ul>
    </aside>

    <main class="main-content" style="max-width: 600px;">
        <h2>Edit Club Information</h2>
        <p class="text-muted">Modify name or descriptions of the selected club.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo escape($error); ?></div>
        <?php endif; ?>

        <form action="edit-club.php?club_id=<?php echo $clubId; ?>" method="POST" style="margin-top: 20px;">
            <?php csrfInput(); ?>

            <div class="form-group">
                <label for="name">Club Name</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="Club Name" required value="<?php echo escape($club['name']); ?>">
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="5" placeholder="Club Description..."><?php echo escape($club['description']); ?></textarea>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="clubs.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
