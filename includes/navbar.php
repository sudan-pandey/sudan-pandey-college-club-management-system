<?php
// Nav bar that dynamically renders links based on user role
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$role = $_SESSION['user_role'] ?? '';
$fullName = $_SESSION['user_name'] ?? 'User';
?>
<header class="app-header">
    <div class="container header-container">
        <a href="../index.php" class="logo">🎯 ClubManager</a>
        <nav class="app-nav">
            <?php if ($role === 'admin'): ?>
                <a href="../admin/dashboard.php">Admin Panel</a>
            <?php elseif ($role === 'club_head'): ?>
                <a href="../club-head/dashboard.php">Club Head Panel</a>
            <?php elseif ($role === 'student'): ?>
                <a href="../student/dashboard.php">Student Hub</a>
            <?php endif; ?>
            <span style="color: var(--text-muted); margin-left: 20px;">Hi, <?php echo htmlspecialchars($fullName); ?></span>
            <a href="../logout.php" class="btn btn-outline btn-sm" style="margin-left: 15px;">Logout</a>
        </nav>
    </div>
</header>
