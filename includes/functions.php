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