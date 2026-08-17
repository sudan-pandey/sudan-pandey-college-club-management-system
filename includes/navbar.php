<?php
// Nav bar that dynamically renders links based on user role
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$role = $_SESSION['user_role'] ?? '';
$fullName = $_SESSION['user_name'] ?? 'User';

$roleLabel = 'User';
$profileUrl = '#';
if ($role === 'club_head') {
    $roleLabel = 'Club Head';
    $profileUrl = '../club-head/profile.php';
} elseif ($role === 'admin') {
    $roleLabel = 'Admin';
    $profileUrl = '../admin/profile.php';
} elseif ($role === 'student') {
    $roleLabel = 'Student';
    $profileUrl = '../student/profile.php';
}

// Determine photo URL if uploaded
$photoUrl = $_SESSION['profile_picture'] ?? $_SESSION['user_photo'] ?? '';

// Fallback to club head logo if assigned and photoUrl empty
if (empty($photoUrl) && $role === 'club_head' && isset($pdo)) {
    try {
        $stmtClubLogo = $pdo->prepare("SELECT logo FROM clubs WHERE club_head_id = ? LIMIT 1");
        $stmtClubLogo->execute([$_SESSION['user_id'] ?? 0]);
        $cLogo = $stmtClubLogo->fetchColumn();
        if ($cLogo && file_exists(__DIR__ . '/../uploads/clubs/' . $cLogo)) {
            $photoUrl = '../uploads/clubs/' . $cLogo;
        }
    } catch (Exception $e) {
        // Fallback silently if DB unavailable
    }
}
?>
<header class="app-header">
    <div class="container header-container">
        <a href="../index.php" class="logo">🎯 ClubManager</a>

        <?php if (!empty($_SESSION['user_id'])): ?>
            <div class="header-profile-block">
                <a href="<?php echo htmlspecialchars($profileUrl); ?>" class="header-profile-link" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit;" title="View Profile Settings">
                    <div class="user-text-info">
                        <span class="profile-name"><?php echo htmlspecialchars($fullName); ?></span>
                        <span class="profile-role"><?php echo htmlspecialchars($roleLabel); ?></span>
                    </div>
                    <div class="profile-avatar-wrapper">
                        <?php if (!empty($photoUrl)): ?>
                            <img id="headerProfileImg" src="<?php echo htmlspecialchars($photoUrl); ?>" alt="<?php echo htmlspecialchars($fullName); ?> Profile" class="header-profile-img">
                        <?php else: ?>
                            <img id="headerProfileImg" src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='40' height='40' viewBox='0 0 24 24' fill='%2394a3b8'><path d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z'/></svg>" alt="<?php echo htmlspecialchars($fullName); ?> Profile" class="header-profile-img">
                        <?php endif; ?>
                    </div>
                </a>
                <a href="../logout.php" class="header-logout-btn" title="Logout" aria-label="Logout">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                </a>
            </div>
            <script>
                // Client-side clubHeadPhotoURL variable initialization
                window.clubHeadPhotoURL = "<?php echo !empty($photoUrl) ? addslashes($photoUrl) : ''; ?>";
                if (window.clubHeadPhotoURL) {
                    var headerImg = document.getElementById('headerProfileImg');
                    if (headerImg && !headerImg.src.endsWith(window.clubHeadPhotoURL)) {
                        headerImg.src = window.clubHeadPhotoURL;
                    }
                }
            </script>
        <?php else: ?>
            <nav class="app-nav">
                <a href="../login.php" class="btn btn-outline btn-sm">Login</a>
                <a href="../register.php" class="btn btn-primary btn-sm">Register</a>
            </nav>
        <?php endif; ?>
    </div>
</header>
