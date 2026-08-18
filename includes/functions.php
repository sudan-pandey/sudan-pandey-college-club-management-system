<?php
// Common Utility Functions

/**
 * Escapes HTML to protect against XSS
 * @param string $value
 * @return string
 */
function escape($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Renders HTML for a club logo/image or a clean placeholder.
 *
 * @param string|null $logoFilename
 * @param string $clubName
 * @param int $size Width and height in pixels (default: 60)
 * @param string $extraClass Additional CSS classes
 * @return string HTML string
 */
function renderClubLogo($logoFilename, $clubName, $size = 60, $extraClass = '') {
    $sizeStyle = "width: {$size}px; height: {$size}px;";

    // Check if relative path or absolute path exists for logo file
    $logoPath = '../uploads/clubs/' . $logoFilename;
    $rootLogoPath = 'uploads/clubs/' . $logoFilename;
    $src = null;

    if (!empty($logoFilename)) {
        if (file_exists($logoPath)) {
            $src = $logoPath;
        } elseif (file_exists($rootLogoPath)) {
            $src = $rootLogoPath;
        } elseif (file_exists(__DIR__ . '/../uploads/clubs/' . $logoFilename)) {
            $src = '../uploads/clubs/' . $logoFilename;
        }
    }

    if ($src) {
        return sprintf(
            '<img src="%s" alt="%s Logo" class="club-logo-img %s" style="%s object-fit: cover; border-radius: 12px; border: 1px solid var(--border-color); flex-shrink: 0;" />',
            escape($src),
            escape($clubName),
            escape($extraClass),
            $sizeStyle
        );
    }

    // Default icon/placeholder SVG or initial letter badge
    $initial = strtoupper(substr($clubName, 0, 1));
    $fontSize = max(0.9, round($size * 0.4 / 16, 2)) . 'rem';

    return sprintf(
        '<div class="club-logo-placeholder %s" style="%s border-radius: 12px; background: var(--border-color, #334155); color: #818cf8; display: inline-flex; align-items: center; justify-content: center; font-size: %s; font-weight: 700; flex-shrink: 0; border: 1px solid var(--border-color);"><svg width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="opacity: 0.9;"><path d="M3 21h18"></path><path d="M5 21V7l8-4v18"></path><path d="M19 21V11l-6-3"></path><path d="M9 9l0 .01"></path><path d="M9 12l0 .01"></path></svg></div>',
        escape($extraClass),
        $sizeStyle,
        $fontSize,
        round($size * 0.5),
        round($size * 0.5)
    );
}

/**
 * Gets the count of unread announcements for a student user.
 *
 * @param PDO $pdo
 * @param int $userId
 * @return int
 */
function getUnreadAnnouncementsCount($pdo, $userId) {
    if (!$userId || !$pdo) return 0;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*)
                               FROM announcements a
                               LEFT JOIN announcement_reads ar ON a.id = ar.announcement_id AND ar.user_id = ?
                               WHERE ar.announcement_id IS NULL");
        $stmt->execute([$userId]);
        return intval($stmt->fetchColumn());
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Gets the count of pending/in-progress tasks for a club managed by a club head.
 *
 * @param PDO $pdo
 * @param int $userId
 * @return int
 */
function getClubHeadPendingTasksCount($pdo, $userId) {
    if (!$userId || !$pdo) return 0;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(t.id)
                               FROM tasks t
                               JOIN clubs c ON t.club_id = c.id
                               WHERE c.club_head_id = ? AND t.status IN ('pending', 'in_progress')");
        $stmt->execute([$userId]);
        return intval($stmt->fetchColumn());
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Gets the count of all pending/in-progress tasks system-wide for admin.
 *
 * @param PDO $pdo
 * @return int
 */
function getAllPendingTasksCount($pdo) {
    if (!$pdo) return 0;
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status IN ('pending', 'in_progress')");
        return intval($stmt->fetchColumn());
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Gets the count of uncompleted assigned tasks for a student user.
 *
 * @param PDO $pdo
 * @param int $userId
 * @return int
 */
function getPendingTasksCount($pdo, $userId) {
    if (!$userId || !$pdo) return 0;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*)
                               FROM tasks
                               WHERE assigned_to = ? AND status IN ('pending', 'in_progress')");
        $stmt->execute([$userId]);
        return intval($stmt->fetchColumn());
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Renders success/error alerts from GET query parameters
 */
function displayAlerts() {
    if (isset($_GET['success'])) {
        echo '<div class="alert alert-success">' . escape($_GET['success']) . '</div>';
    }
    if (isset($_GET['error'])) {
        echo '<div class="alert alert-danger">' . escape($_GET['error']) . '</div>';
    }
}

/**
 * Checks if a task is overdue
 * @param string $deadline
 * @param string $status
 * @return bool
 */
function isTaskOverdue($deadline, $status) {
    if ($status === 'completed' || $status === 'cancelled') {
        return false;
    }
    return strtotime($deadline) < strtotime(date('Y-m-d'));
}

/**
 * Sends a membership approval email to a student.
 * Uses custom template from clubs table if present, otherwise uses default template.
 *
 * @param PDO $pdo
 * @param int $studentUserId
 * @param int $clubId
 * @return array ['success' => bool, 'message' => string]
 */
function sendMembershipApprovalEmail($pdo, $studentUserId, $clubId) {
    try {
        // Fetch student info
        $stmtUser = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ? LIMIT 1");
        $stmtUser->execute([$studentUserId]);
        $student = $stmtUser->fetch();

        if (!$student) {
            return ['success' => false, 'message' => 'Student user not found.'];
        }

        // Fetch club and club head info
        $stmtClub = $pdo->prepare("SELECT c.*, u.full_name AS head_name 
                                   FROM clubs c 
                                   LEFT JOIN users u ON c.club_head_id = u.id 
                                   WHERE c.id = ? LIMIT 1");
        $stmtClub->execute([$clubId]);
        $club = $stmtClub->fetch();

        if (!$club) {
            return ['success' => false, 'message' => 'Club not found.'];
        }

        $studentName = $student['full_name'];
        $studentEmail = $student['email'];
        $clubName = $club['name'];
        $clubHeadName = $club['head_name'] ?: 'Club Head';

        // Custom template check
        $subjectTemplate = !empty($club['email_subject']) ? $club['email_subject'] : "Club Membership Approved - {club_name}";
        $bodyTemplate = !empty($club['email_body']) ? $club['email_body'] : "Dear {student_name},\n\nCongratulations!\n\nYour request to join {club_name} has been approved.\n\nYou are now an active member of {club_name}.\n\nClub Head:\n{club_head_name}\n\nYou can now log in to the College Club Management System.\n\nRegards,\n{club_name}";

        // Replace placeholders
        $placeholders = [
            '{student_name}' => $studentName,
            '{club_name}' => $clubName,
            '{club_head_name}' => $clubHeadName,
            '{student_email}' => $studentEmail
        ];

        $subject = strtr($subjectTemplate, $placeholders);
        $body = strtr($bodyTemplate, $placeholders);

        $headers = "From: no-reply@college.edu\r\n" .
                   "Reply-To: no-reply@college.edu\r\n" .
                   "X-Mailer: PHP/" . phpversion();

        // Attempt mail sending
        $sent = @mail($studentEmail, $subject, $body, $headers);

        if ($sent) {
            return ['success' => true, 'message' => 'Email sent successfully.'];
        } else {
            return ['success' => false, 'message' => 'Email could not be sent (SMTP/mail configuration check required). Student remains an active member.'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error attempting email dispatch: ' . $e->getMessage()];
    }
}
?>