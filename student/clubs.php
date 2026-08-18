<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('student');

$userId = $_SESSION['user_id'];

try {
    // Check active membership
    $membership = getActiveMembership($pdo, $userId);

    // Check pending membership request
    $stmtPendingReq = $pdo->prepare("SELECT m.*, c.name AS club_name FROM memberships m JOIN clubs c ON m.club_id = c.id WHERE m.user_id = ? AND m.status = 'pending' LIMIT 1");
    $stmtPendingReq->execute([$userId]);
    $pendingMembership = $stmtPendingReq->fetch();

    // Fetch all clubs
    $stmt = $pdo->query("SELECT c.*, u.full_name AS head_name,
                           (SELECT COUNT(*) FROM memberships m WHERE m.club_id = c.id AND m.status = 'active') AS member_count
                           FROM clubs c
                           LEFT JOIN users u ON c.club_head_id = u.id
                           ORDER BY c.name ASC");
    $clubs = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}

$oneClubModal = $_SESSION['one_club_modal'] ?? null;
unset($_SESSION['one_club_modal']);
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/navbar.php'; ?>

<div class="container dashboard-container">
    <?php require_once '../includes/sidebar.php'; ?>

    <main class="main-content">
        <h2>Browse Clubs Directory</h2>
        <p class="text-muted">A student can belong to <strong>only one active club at a time</strong>. Enforced on the server.</p>

        <?php displayAlerts(); ?>

        <div class="card-grid">
            <?php foreach ($clubs as $club): ?>
                <div class="card">
                    <div>
                        <div style="display: flex; align-items: flex-start; gap: 16px; margin-bottom: 16px;">
                            <?php echo renderClubLogo($club['logo'] ?? null, $club['name'], 56); ?>
                            <div>
                                <h3 style="margin-top: 0; margin-bottom: 4px;"><?php echo escape($club['name']); ?></h3>
                                <div style="font-size: 0.85rem; color: var(--text-muted);">
                                    Managed by <strong><?php echo $club['head_name'] ? escape($club['head_name']) : 'Unassigned'; ?></strong>
                                </div>
                            </div>
                        </div>
                        <p><?php echo escape($club['description']); ?></p>
                        <div style="font-size: 0.9rem; margin-bottom: 20px;">
                            <div><span class="text-muted">Club Head:</span> <strong><?php echo $club['head_name'] ? escape($club['head_name']) : 'Unassigned'; ?></strong></div>
                            <div><span class="text-muted">Total Active Members:</span> <strong><?php echo $club['member_count']; ?></strong></div>
                        </div>
                    </div>

                    <div style="margin-top: auto;">
                        <?php if ($membership && intval($membership['club_id']) === intval($club['id'])): ?>
                            <span class="status-badge status-active" style="display: block; text-align: center; padding: 8px;">✓ Your Active Club</span>
                        <?php elseif ($pendingMembership && intval($pendingMembership['club_id']) === intval($club['id'])): ?>
                            <span class="status-badge status-pending" style="display: block; text-align: center; padding: 8px; background: #f39c12; color: #fff;">⏳ Request Pending</span>
                        <?php elseif ($membership): ?>
                            <form action="join-club.php" method="POST">
                                <?php csrfInput(); ?>
                                <input type="hidden" name="club_id" value="<?php echo $club['id']; ?>">
                                <button type="submit" class="btn btn-secondary" style="width: 100%;">Join Club</button>
                            </form>
                        <?php elseif ($pendingMembership): ?>
                            <button class="btn btn-secondary" style="width: 100%; cursor: not-allowed;" disabled title="You have a pending request">Request Pending</button>
                        <?php else: ?>
                            <form action="join-club.php" method="POST">
                                <?php csrfInput(); ?>
                                <input type="hidden" name="club_id" value="<?php echo $club['id']; ?>">
                                <button type="submit" class="btn btn-primary" style="width: 100%;">Join Club</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<?php if ($oneClubModal): ?>
<div id="oneClubModal" class="modal-overlay">
    <div class="modal-content" style="text-align: center;">
        <h3 style="margin-top: 0; color: var(--text-main);">Club Membership</h3>
        <p style="margin: 20px 0; font-size: 1rem; line-height: 1.6; color: var(--text-muted);">
            You can join only one club at a time.<br><br>
            You are currently a member of <strong style="color: var(--text-main);"><?php echo escape($oneClubModal['active_club']); ?></strong>.<br><br>
            Please leave your current club and wait for approval before joining another club.
        </p>
        <button onclick="document.getElementById('oneClubModal').style.display='none'" class="btn btn-primary" style="min-width: 120px;">OK</button>
    </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
