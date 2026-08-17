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
            $logoFilename = $club['logo'] ?? null;

            // Handle file upload if provided
            if (isset($_FILES['club_logo']) && $_FILES['club_logo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES['club_logo'];

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $error = "File upload failed with error code " . $file['error'];
                } elseif ($file['size'] > 5 * 1024 * 1024) {
                    $error = "File size exceeds maximum allowed limit of 5 MB.";
                } else {
                    $origName = $file['name'];
                    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);
                    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

                    if (!in_array($ext, $allowedExts) || !in_array($mimeType, $allowedMimes)) {
                        $error = "Invalid file type. Only JPG, JPEG, PNG, and WEBP images are allowed.";
                    } else {
                        $uploadDir = '../uploads/clubs/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }

                        $newFilename = 'club_' . $clubId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                        $targetPath = $uploadDir . $newFilename;

                        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                            // Delete old logo if exists
                            if (!empty($club['logo']) && file_exists($uploadDir . $club['logo'])) {
                                @unlink($uploadDir . $club['logo']);
                            }
                            $logoFilename = $newFilename;
                        } else {
                            $error = "Failed to save uploaded image to destination folder.";
                        }
                    }
                }
            }

            if (empty($error)) {
                try {
                    // Check name uniqueness excluding current
                    $uStmt = $pdo->prepare("SELECT id FROM clubs WHERE name = ? AND id != ? LIMIT 1");
                    $uStmt->execute([$name, $clubId]);
                    if ($uStmt->fetch()) {
                        $error = "A club with this name already exists.";
                    } else {
                        $updateStmt = $pdo->prepare("UPDATE clubs SET name = ?, description = ?, logo = ? WHERE id = ?");
                        $updateStmt->execute([$name, $description, $logoFilename, $clubId]);
                        header("Location: clubs.php?success=" . urlencode("Club updated successfully!"));
                        exit;
                    }
                } catch (PDOException $e) {
                    $error = "Database Error: " . $e->getMessage();
                }
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
            <li><a href="profile.php">👤 Profile Settings</a></li>
        </ul>
    </aside>

    <main class="main-content" style="max-width: 600px;">
        <h2>Edit Club Information</h2>
        <p class="text-muted">Modify name or descriptions of the selected club.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo escape($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($club['logo']) && file_exists('../uploads/clubs/' . $club['logo'])): ?>
            <div style="margin-top: 20px; text-align: center;">
                <img src="../uploads/clubs/<?php echo escape($club['logo']); ?>" alt="<?php echo escape($club['name']); ?> Logo" style="width: 100px; height: 100px; object-fit: cover; border-radius: 12px; border: 2px solid var(--border-color);">
            </div>
        <?php endif; ?>

        <form action="edit-club.php?club_id=<?php echo $clubId; ?>" method="POST" enctype="multipart/form-data" style="margin-top: 20px;">
            <?php csrfInput(); ?>

            <div class="form-group">
                <label for="name">Club Name</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="Club Name" required value="<?php echo escape($club['name']); ?>">
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="5" placeholder="Club Description..."><?php echo escape($club['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="club_logo">Club Profile Image / Logo (Max 5 MB, JPG/PNG/WEBP)</label>
                <input type="file" id="club_logo" name="club_logo" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="clubs.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
