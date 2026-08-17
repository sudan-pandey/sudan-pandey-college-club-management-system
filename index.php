<?php
// Main landing redirect / welcome page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If user is already logged in, redirect them to their respective dashboards
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'] ?? '';
    if ($role === 'admin') {
        header("Location: admin/dashboard.php");
        exit;
    } elseif ($role === 'club_head') {
        header("Location: club-head/dashboard.php");
        exit;
    } else {
        header("Location: student/dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Club Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="landing-body">
    <header class="landing-header">
        <div class="container header-container">
            <h1 class="logo">🎯 ClubManager</h1>
            <nav class="landing-nav">
                <a href="login.php" class="btn btn-outline">Login</a>
                <a href="register.php" class="btn btn-primary">Register</a>
            </nav>
        </div>
    </header>

    <main class="landing-main">
        <section class="hero-section">
            <div class="container hero-container">
                <div class="hero-content">
                    <span class="badge">TU BCA 4th-Sem Project</span>
                    <h2>Empower Your College Clubs & Communities</h2>
                    <p>A simple, secure, and modern hub to coordinate events, assign roles, complete tasks, track attendance, and gather feedback seamlessly.</p>
                    <div class="hero-actions">
                        <a href="register.php" class="btn btn-primary btn-lg">Get Started</a>
                        <a href="login.php" class="btn btn-secondary btn-lg">Access Dashboard</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="features-section">
            <div class="container">
                <h3 class="section-title">Core Capabilities</h3>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">🛡️</div>
                        <h4>Role & Join Rules</h4>
                        <p>Strict "one active club per student" enforcement and designated responsibilities managed safely on the server side.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">📅</div>
                        <h4>Event Management</h4>
                        <p>Organize events, capture registrations, trace attendance, and aggregate reviews without effort.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">✅</div>
                        <h4>Task Coordination</h4>
                        <p>Club Heads assign tasks to specific roles; students log in to start work and mark their items as done.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">📢</div>
                        <h4>Global Monitoring</h4>
                        <p>Admins can view and govern club status, user roles, assignments, feedback, and logs on a simple dashboard.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="landing-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> College Club Management System. Built with PHP & MySQL.</p>
        </div>
    </footer>
</body>
</html>
