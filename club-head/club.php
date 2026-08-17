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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $error = "CSRF verification failed.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_profile') {
            $description = trim($_POST['description'] ?? '');
            $logoFilename = $club['logo'] ?? null;

            // Handle file upload if provided
            if (isset($_FILES['club_logo']) && $_FILES['club_logo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES['club_logo'];

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $error = "File upload failed with error code " . $file['error'];
                } elseif ($file['size'] > 5 * 1024 * 1024) {
                    $error = "File size exceeds maximum allowed limit of 5 MB.";
                } else {
                    // Validate extension
                    $origName = $file['name'];
                    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

                    // Validate MIME type
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

                        // Generate safe unique filename
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
                    $upStmt = $pdo->prepare("UPDATE clubs SET description = ?, logo = ? WHERE id = ?");
                    $upStmt->execute([$description, $logoFilename, $clubId]);
                    $success = "Club profile details updated successfully!";
                    $club = getOwnClub($pdo, $userId);
                } catch (Exception $e) {
                    $error = "Failed to update profile: " . $e->getMessage();
                }
            }
        } elseif ($action === 'save_email_template') {
            $emailSubject = trim($_POST['email_subject'] ?? '');
            $emailBody = trim($_POST['email_body'] ?? '');

            try {
                $upStmt = $pdo->prepare("UPDATE clubs SET email_subject = ?, email_body = ? WHERE id = ?");
                $upStmt->execute([$emailSubject ?: null, $emailBody ?: null, $clubId]);
                $success = "Custom approval email template updated successfully!";
                
                // Refresh club details
                $club = getOwnClub($pdo, $userId);
            } catch (Exception $e) {
                $error = "Failed to update email template: " . $e->getMessage();
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
            <li><a href="club.php" class="active">🏛️ Club Details</a></li>
            <li><a href="members.php">👥 Members List</a></li>
            <li><a href="events.php">📅 Club Events</a></li>
            <li><a href="calendar.php">🗓️ Calendar View</a></li>
            <li><a href="registrations.php">📝 Event Registrations</a></li>
            <li><a href="attendance.php">✓ Mark Attendance</a></li>
            <li><a href="announcements.php">📢 Announcements</a></li>
            <li><a href="feedback.php">⭐ Feedback Reviews</a></li>
            <li><a href="tasks.php">✅ Task Coordination</a></li>
            <li><a href="profile.php">👤 Profile Settings</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2>Club Information Directory</h2>
        <p class="text-muted">General structure of the student organization managed by you.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo escape($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo escape($success); ?></div>
        <?php endif; ?>

        <div class="feature-card" style="margin-top: 25px;">
            <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
                <?php if (!empty($club['logo']) && file_exists('../uploads/clubs/' . $club['logo'])): ?>
                    <img src="../uploads/clubs/<?php echo escape($club['logo']); ?>" alt="<?php echo escape($club['name']); ?> Logo" style="width: 90px; height: 90px; object-fit: cover; border-radius: 12px; border: 2px solid var(--border-color);">
                <?php else: ?>
                    <div style="width: 90px; height: 90px; border-radius: 12px; background: var(--primary, #2c3e50); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 2.2rem; font-weight: bold;">
                        <?php echo strtoupper(substr($club['name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <div>
                    <h3 style="margin: 0;"><?php echo escape($club['name']); ?></h3>
                    <p class="text-muted" style="margin-top: 5px; font-size: 0.95rem;">Managed by <?php echo escape($_SESSION['user_name'] ?? 'Club Head'); ?></p>
                </div>
            </div>

            <hr style="border-color: var(--border-color); margin: 25px 0;">

            <form action="club.php" method="POST" enctype="multipart/form-data">
                <?php csrfInput(); ?>
                <input type="hidden" name="action" value="update_profile">

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="description">Club Description</label>
                    <textarea id="description" name="description" class="form-control" rows="4" required><?php echo escape($club['description']); ?></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 25px;">
                    <label for="club_logo">Club Profile Image / Logo (Max 5 MB, JPG/PNG/WEBP)</label>
                    <input type="file" id="club_logo" name="club_logo" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                </div>

                <button type="submit" class="btn btn-primary">Update Club Profile</button>
            </form>
        </div>

        <div class="feature-card" style="margin-top: 25px;">
            <h3>✉️ Customize Membership Approval Email</h3>
            <p class="text-muted" style="margin-bottom: 20px;">
                Personalize the email sent to students when their membership is approved for <strong><?php echo escape($club['name']); ?></strong>.<br>
                Available placeholders: <code>{student_name}</code>, <code>{club_name}</code>, <code>{club_head_name}</code>, <code>{student_email}</code>
            </p>

            <form action="club.php" method="POST">
                <?php csrfInput(); ?>
                <input type="hidden" name="action" value="save_email_template">

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="email_subject">Email Subject</label>
                    <input type="text" id="email_subject" name="email_subject" class="form-control" 
                           placeholder="e.g. Welcome to {club_name}!" 
                           value="<?php echo escape($club['email_subject'] ?? ''); ?>">
                    <small class="text-muted">Leave blank to use the default subject: <em>Club Membership Approved - {club_name}</em></small>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="email_body">Email Message Body</label>
                    <textarea id="email_body" name="email_body" class="form-control" rows="8" 
                              placeholder="Dear {student_name}, ..."><?php echo escape($club['email_body'] ?? ''); ?></textarea>
                    <small class="text-muted">Leave blank to use the default notification message body.</small>
                </div>

                <button type="submit" class="btn btn-primary">Save Email Template</button>
            </form>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
