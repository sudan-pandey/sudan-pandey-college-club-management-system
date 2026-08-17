<?php
// Authentication and Role Authorization Helpers

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Refreshes user data (full_name, email, role, status) from the database
 * on every request so role changes take effect immediately on page refresh.
 *
 * @param PDO|null $pdo
 * @return string Current user role
 */
function refreshSessionUserData($pdo = null) {
    if (!isset($_SESSION['user_id'])) {
        return '';
    }

    if (!$pdo) {
        global $pdo;
    }

    if (!$pdo) {
        return $_SESSION['user_role'] ?? '';
    }

    try {
        $stmt = $pdo->prepare("SELECT full_name, email, role, status FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user || $user['status'] !== 'active') {
            session_unset();
            session_destroy();
            header("Location: ../login.php?error=" . urlencode("Your account is inactive or no longer exists."));
            exit;
        }

        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];

        return $user['role'];
    } catch (Exception $e) {
        return $_SESSION['user_role'] ?? '';
    }
}

/**
 * Ensures user is authenticated. Otherwise redirects to login.
 */
function requireLogin() {
    global $pdo;
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit;
    }
    refreshSessionUserData($pdo);
}

/**
 * Ensures user has a matching role or admin.
 * @param string|array $allowed_roles
 */
function requireRole($allowed_roles) {
    global $pdo;
    requireLogin();

    $user_role = refreshSessionUserData($pdo);

    $isAllowed = false;
    if ($user_role === 'admin') {
        $isAllowed = true;
    } elseif (is_array($allowed_roles)) {
        $isAllowed = in_array($user_role, $allowed_roles);
    } else {
        $isAllowed = ($user_role === $allowed_roles);
    }

    if (!$isAllowed) {
        // Redirect seamlessly to the dashboard corresponding to their current role
        if ($user_role === 'admin') {
            header("Location: ../admin/dashboard.php");
        } elseif ($user_role === 'club_head') {
            header("Location: ../club-head/dashboard.php");
        } else {
            header("Location: ../student/dashboard.php");
        }
        exit;
    }
}

/**
 * Get active student club membership
 * @param PDO $pdo
 * @param int $user_id
 * @return array|false
 */
function getActiveMembership($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT m.*, c.name AS club_name, r.name AS responsibility_name
                           FROM memberships m
                           JOIN clubs c ON m.club_id = c.id
                           LEFT JOIN responsibilities r ON m.responsibility_id = r.id
                           WHERE m.user_id = ? AND m.status = 'active' LIMIT 1");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

/**
 * Get own club for Club Head
 * @param PDO $pdo
 * @param int $user_id
 * @return array|false
 */
function getOwnClub($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM clubs WHERE club_head_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}
?>