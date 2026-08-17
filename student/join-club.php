<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

requireRole('student');

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        header("Location: clubs.php?error=" . urlencode("CSRF token validation failed."));
        exit;
    }

    $clubId = intval($_POST['club_id'] ?? 0);

    if ($clubId <= 0) {
        header("Location: clubs.php?error=" . urlencode("Invalid club chosen."));
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Check for Active Membership
        $stmtActive = $pdo->prepare("SELECT m.id, c.name AS club_name 
                                    FROM memberships m 
                                    JOIN clubs c ON m.club_id = c.id 
                                    WHERE m.user_id = ? AND m.status = 'active' LIMIT 1");
        $stmtActive->execute([$userId]);
        $activeClub = $stmtActive->fetch();

        if ($activeClub) {
            $_SESSION['one_club_modal'] = [
                'active_club' => $activeClub['club_name']
            ];
            $pdo->rollBack();
            header("Location: clubs.php");
            exit;
        }

        // 2. Check for Pending Join Request
        $stmtPending = $pdo->prepare("SELECT id FROM memberships WHERE user_id = ? AND status = 'pending' LIMIT 1");
        $stmtPending->execute([$userId]);
        if ($stmtPending->fetch()) {
            $pdo->rollBack();
            header("Location: clubs.php?error=" . urlencode("You already have a pending club membership request. Please wait for the Club Head's decision."));
            exit;
        }

        // Verify target club exists
        $clubCheck = $pdo->prepare("SELECT id FROM clubs WHERE id = ? LIMIT 1");
        $clubCheck->execute([$clubId]);
        if (!$clubCheck->fetch()) {
            throw new Exception("The selected club does not exist.");
        }

        // 3. Insert new join request with status = 'pending'
        $insertStmt = $pdo->prepare("INSERT INTO memberships (user_id, club_id, responsibility_id, status, requested_at) VALUES (?, ?, NULL, 'pending', NOW())");
        $insertStmt->execute([$userId, $clubId]);

        $pdo->commit();
        header("Location: clubs.php?success=" . urlencode("Your request to join the club has been submitted. Please wait for the Club Head's approval."));
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: clubs.php?error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: clubs.php");
    exit;
}
?>