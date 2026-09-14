<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo = get_pdo();
$viewerRole = current_user_role();
$viewerId = (int)($_SESSION['user_id'] ?? 0);
$viewerName = current_user_full_name();

if (in_array($viewerRole, ['ADMIN', 'CLERK'], true)) {
    $casesCount = (int)$pdo->query('SELECT COUNT(*) FROM cases')->fetchColumn();
    $usersCount = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $hearingsCount = (int)$pdo->query('SELECT COUNT(*) FROM hearings')->fetchColumn();
    $documentsCount = (int)$pdo->query('SELECT COUNT(*) FROM documents')->fetchColumn();
} elseif ($viewerRole === 'JUDGE') {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM cases WHERE assigned_judge = ?');
    $stmt->execute([$viewerId]);
    $casesCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM hearings WHERE judge_id = ?');
    $stmt->execute([$viewerId]);
    $hearingsCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role IN (\'JUDGE\',\'ADVOCATE\',\'LITIGANT\')');
    $stmt->execute();
    $usersCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM documents d JOIN cases c ON c.id = d.case_id WHERE c.assigned_judge = ?');
    $stmt->execute([$viewerId]);
    $documentsCount = (int)$stmt->fetchColumn();
} elseif ($viewerRole === 'ADVOCATE') {
    $stmt = $pdo->prepare('SELECT COUNT(DISTINCT c.id) FROM cases c JOIN parties p ON p.case_id = c.id WHERE LOWER(p.advocate_name) = LOWER(?)');
    $stmt->execute([$viewerName]);
    $casesCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role IN (\'ADVOCATE\',\'LITIGANT\')');
    $stmt->execute();
    $usersCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM hearings h JOIN cases c ON c.id = h.case_id JOIN parties p ON p.case_id = c.id WHERE LOWER(p.advocate_name) = LOWER(?)');
    $stmt->execute([$viewerName]);
    $hearingsCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM documents d JOIN cases c ON c.id = d.case_id JOIN parties p ON p.case_id = c.id WHERE LOWER(p.advocate_name) = LOWER(?)');
    $stmt->execute([$viewerName]);
    $documentsCount = (int)$stmt->fetchColumn();
} else {
    $stmt = $pdo->prepare('SELECT COUNT(DISTINCT c.id) FROM cases c JOIN parties p ON p.case_id = c.id WHERE LOWER(p.name) = LOWER(?)');
    $stmt->execute([$viewerName]);
    $casesCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE id = ?');
    $stmt->execute([$viewerId]);
    $usersCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM hearings h JOIN cases c ON c.id = h.case_id JOIN parties p ON p.case_id = c.id WHERE LOWER(p.name) = LOWER(?)');
    $stmt->execute([$viewerName]);
    $hearingsCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM documents d JOIN cases c ON c.id = d.case_id JOIN parties p ON p.case_id = c.id WHERE LOWER(p.name) = LOWER(?)');
    $stmt->execute([$viewerName]);
    $documentsCount = (int)$stmt->fetchColumn();
}

$title = 'Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<section class="page-head">
    <div>
        <span class="page-kicker">Overview</span>
        <h1><?= e(APP_NAME) ?></h1>
    </div>
    <a class="btn btn-primary" href="<?= e(BASE_URL . '/case_create.php') ?>">New Case</a>
</section>

<?= flash_message() ?>

<section class="stats-grid">
    <article class="stat-card">
        <span class="stat-label">Cases</span>
        <span class="stat-number"><?= e($casesCount) ?></span>
    </article>
    <article class="stat-card">
        <span class="stat-label">Users</span>
        <span class="stat-number"><?= e($usersCount) ?></span>
    </article>
    <article class="stat-card">
        <span class="stat-label">Hearings</span>
        <span class="stat-number"><?= e($hearingsCount) ?></span>
    </article>
    <article class="stat-card">
        <span class="stat-label">Documents</span>
        <span class="stat-number"><?= e($documentsCount) ?></span>
    </article>
</section>

<section class="panel">
    <div class="panel-head">
        <h2>Recent case activity</h2>
    </div>
    <div class="simple-list">
        <?php
        if (in_array($viewerRole, ['ADMIN', 'CLERK'], true)) {
            $cases = $pdo->query('SELECT id, case_number, title, status, filing_date FROM cases ORDER BY filing_date DESC LIMIT 6')->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($viewerRole === 'JUDGE') {
            $stmt = $pdo->prepare('SELECT id, case_number, title, status, filing_date FROM cases WHERE assigned_judge = ? ORDER BY filing_date DESC LIMIT 6');
            $stmt->execute([$viewerId]);
            $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($viewerRole === 'ADVOCATE') {
            $stmt = $pdo->prepare('SELECT DISTINCT c.id, c.case_number, c.title, c.status, c.filing_date FROM cases c JOIN parties p ON p.case_id = c.id WHERE LOWER(p.advocate_name) = LOWER(?) ORDER BY c.filing_date DESC LIMIT 6');
            $stmt->execute([$viewerName]);
            $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->prepare('SELECT DISTINCT c.id, c.case_number, c.title, c.status, c.filing_date FROM cases c JOIN parties p ON p.case_id = c.id WHERE LOWER(p.name) = LOWER(?) ORDER BY c.filing_date DESC LIMIT 6');
            $stmt->execute([$viewerName]);
            $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if (!$cases):
            echo '<div class="empty-state">No cases registered yet.</div>';
        else:
            foreach ($cases as $case):
                echo '<div class="simple-list-item">';
                echo '<span><a href="' . e(BASE_URL . '/case_view.php?id=' . $case['id']) . '">' . e($case['case_number']) . ' - ' . e($case['title']) . '</a></span>';
                echo '<span class="badge badge-' . e(strtolower($case['status'])) . '">' . e($case['status']) . '</span>';
                echo '</div>';
            endforeach;
        endif;
        ?>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
