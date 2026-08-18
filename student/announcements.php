<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

requireRole('student');

$userId = $_SESSION['user_id'];

try {
    $membership = getActiveMembership($pdo, $userId);

    // Fetch announcements along with read status for current student
    $stmt = $pdo->prepare("SELECT a.*,
                                  COALESCE(c.name, 'System Admin') AS sender_name,
                                  c.logo AS club_logo,
                                  u.full_name AS publisher,
                                  (SELECT read_at FROM announcement_reads ar WHERE ar.announcement_id = a.id AND ar.user_id = ? LIMIT 1) AS read_at
                           FROM announcements a
                           LEFT JOIN clubs c ON a.club_id = c.id
                           LEFT JOIN users u ON a.created_by = u.id
                           ORDER BY a.created_at DESC");
    $stmt->execute([$userId]);
    $announcements = $stmt->fetchAll();

    // Automatically mark all current announcements as read when student views page
    if (!empty($announcements)) {
        $markStmt = $pdo->prepare("INSERT IGNORE INTO announcement_reads (announcement_id, user_id) VALUES (?, ?)");
        foreach ($announcements as $ann) {
            $markStmt->execute([$ann['id'], $userId]);
        }
    }
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
                        <?php $isUnread = empty($ann['read_at']); ?>
                        <div class="feature-card" style="border-left: 4px solid <?php echo $badgeColor; ?>; position: relative;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <?php if (!empty($ann['club_id'])): ?>
                                        <?php echo renderClubLogo($ann['club_logo'] ?? null, $sender, 32); ?>
                                    <?php endif; ?>
                                    <span class="status-badge" style="background-color: var(--border-color); color: var(--text-main); padding: 4px 10px; font-weight: 600;">
                                        🏛️ <?php echo escape($sender); ?>
                                    </span>
                                    <span class="status-badge" style="background-color: <?php echo $badgeColor; ?>; color: #fff; padding: 4px 10px; font-weight: 600;">
                                        📌 <?php echo escape($priority); ?>
                                    </span>
                                    <?php if ($isUnread): ?>
                                        <span class="status-badge" style="background-color: #ef4444; color: #fff; padding: 2px 8px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">
                                            NEW
                                        </span>
                                    <?php endif; ?>
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
