<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo = get_pdo();
$viewerRole = current_user_role();
$viewerId = (int)($_SESSION['user_id'] ?? 0);
$viewerName = current_user_full_name();

if (!$pdo) {
    $title = 'Cases';
    include __DIR__ . '/../includes/header.php';
    ?>
    <section class="page-head">
        <div>
            <span class="page-kicker">Registry</span>
            <h1>Cases</h1>
        </div>
    </section>
    <section class="panel">
        <div class="panel-head">
            <h2>Database unavailable</h2>
        </div>
        <p class="empty-state">The PostgreSQL connection could not be established. Set a working DB_HOST, DB_PORT, DB_NAME, DB_USER, and DB_PASS environment value or a valid DATABASE_URL.</p>
    </section>
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit;
}

if (in_array($viewerRole, ['ADMIN', 'CLERK'], true)) {
    $cases = $pdo->query('SELECT c.*, u.full_name AS judge_name FROM cases c LEFT JOIN users u ON u.id = c.assigned_judge ORDER BY c.filing_date DESC')->fetchAll(PDO::FETCH_ASSOC);
} elseif ($viewerRole === 'JUDGE') {
    $stmt = $pdo->prepare('SELECT c.*, u.full_name AS judge_name FROM cases c LEFT JOIN users u ON u.id = c.assigned_judge WHERE c.assigned_judge = ? ORDER BY c.filing_date DESC');
    $stmt->execute([$viewerId]);
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($viewerRole === 'ADVOCATE') {
    $stmt = $pdo->prepare('SELECT DISTINCT c.*, u.full_name AS judge_name FROM cases c LEFT JOIN users u ON u.id = c.assigned_judge JOIN parties p ON p.case_id = c.id WHERE LOWER(p.advocate_name) = LOWER(?) ORDER BY c.filing_date DESC');
    $stmt->execute([$viewerName]);
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare('SELECT DISTINCT c.*, u.full_name AS judge_name FROM cases c LEFT JOIN users u ON u.id = c.assigned_judge JOIN parties p ON p.case_id = c.id WHERE LOWER(p.name) = LOWER(?) ORDER BY c.filing_date DESC');
    $stmt->execute([$viewerName]);
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$title = 'Cases';
include __DIR__ . '/../includes/header.php';
?>
<section class="page-head">
    <div>
        <span class="page-kicker">Registry</span>
        <h1>Cases</h1>
    </div>
    <a class="btn btn-primary" href="<?= e(BASE_URL . '/case_create.php') ?>">Add Case</a>
</section>

<?= flash_message() ?>

<section class="panel">
    <div class="panel-head">
        <h2>Case List</h2>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Case Number</th>
                <th>Title</th>
                <th>Type</th>
                <th>Status</th>
                <th>Assigned Judge</th>
                <th>Filing Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cases as $c): ?>
                <tr>
                    <td><?= e($c['case_number']) ?></td>
                    <td><?= e($c['title']) ?></td>
                    <td><?= e($c['case_type']) ?></td>
                    <td><span class="badge badge-<?= e(strtolower($c['status'])) ?>"><?= e($c['status']) ?></span></td>
                    <td><?= e($c['judge_name'] ?: 'Unassigned') ?></td>
                    <td><?= e(format_date($c['filing_date'])) ?></td>
                    <td><a class="link-button" href="<?= e(BASE_URL . '/case_view.php?id=' . $c['id']) ?>">View</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
