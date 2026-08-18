<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

requireRole('student');

$userId = $_SESSION['user_id'];

try {
    $membership = getActiveMembership($pdo, $userId);

    // Centralized Announcements: Fetch announcements from ALL clubs and system admin
    $stmt = $pdo->query("SELECT a.*,
                                COALESCE(c.name, 'System Admin') AS sender_name,
                                u.full_name AS publisher
                         FROM announcements a
                         LEFT JOIN clubs c ON a.club_id = c.id
                         LEFT JOIN users u ON a.created_by = u.id
                         ORDER BY a.created_at DESC");
    $announcements = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/navbar.php'; ?>

<div class="container dashboard-container">
    <?php require_once '../includes/sidebar.php'; ?>

    <main class="main-content">
        <h2>Announcements Board</h2>
        <p class="text-muted">Stay informed with updates, notices, and broadcasts from all student clubs and system administration.</p>

        <div style="margin-top: 25px;">
            <?php if (empty($announcements)): ?>
                <p class="text-muted" style="font-style: italic;">No announcements have been published yet.</p>
            <?php else: ?>
                <div class="card-grid" style="grid-template-columns: 1fr; gap: 20px;">
                    <?php foreach ($announcements as $ann): ?>
                        <?php
                            $priority = !empty($ann['priority']) ? $ann['priority'] : 'General';
                            $badgeColor = 'var(--primary-color)';
                            if ($priority === 'Urgent') {
                                $badgeColor = 'var(--danger)';
                            } elseif ($priority === 'Event') {
                                $badgeColor = 'var(--info)';
                            }
                            $sender = !empty($ann['sender_name']) ? $ann['sender_name'] : 'System Admin';
                        ?>
                        <div class="feature-card" style="border-left: 4px solid <?php echo $badgeColor; ?>;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span class="status-badge" style="background-color: var(--border-color); color: var(--text-main); padding: 4px 10px; font-weight: 600;">
                                        🏛️ <?php echo escape($sender); ?>
                                    </span>
                                    <span class="status-badge" style="background-color: <?php echo $badgeColor; ?>; color: #fff; padding: 4px 10px; font-weight: 600;">
                                        📌 <?php echo escape($priority); ?>
                                    </span>
                                </div>
                                <span class="text-muted" style="font-size: 0.85rem;">🕒 <?php echo escape($ann['created_at']); ?></span>
                            </div>

                            <h3 style="margin-bottom: 10px; color: var(--text-main); font-size: 1.25rem;"><?php echo escape($ann['title']); ?></h3>
                            <p style="color: var(--text-main); margin-bottom: 15px; line-height: 1.6; white-space: pre-wrap;"><?php echo escape($ann['content']); ?></p>

                            <div style="border-top: 1px dashed var(--border-color); padding-top: 10px; color: var(--text-muted); font-size: 0.85rem;">
                                Posted by: <strong><?php echo escape($ann['publisher'] ?: 'System Admin'); ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
