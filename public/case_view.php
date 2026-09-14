<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo = get_pdo();
$id = (int)($_GET['id'] ?? 0);
$case = $pdo->prepare('SELECT c.*, j.full_name AS judge_name, cb.full_name AS created_by_name FROM cases c LEFT JOIN users j ON j.id = c.assigned_judge LEFT JOIN users cb ON cb.id = c.created_by WHERE c.id = ?');
$case->execute([$id]);
$case = $case->fetch(PDO::FETCH_ASSOC);

if (!$case) {
    flash('error', 'Case not found.');
    redirect('cases.php');
}

$parties = $pdo->prepare('SELECT * FROM parties WHERE case_id = ? ORDER BY party_type');
$parties->execute([$id]);
$parties = $parties->fetchAll(PDO::FETCH_ASSOC);

$hearings = $pdo->prepare('SELECT h.*, u.full_name AS judge_name FROM hearings h LEFT JOIN users u ON u.id = h.judge_id WHERE h.case_id = ? ORDER BY h.hearing_date DESC');
$hearings->execute([$id]);
$hearings = $hearings->fetchAll(PDO::FETCH_ASSOC);

$orders = $pdo->prepare('SELECT * FROM orders WHERE case_id = ? ORDER BY order_date DESC');
$orders->execute([$id]);
$orders = $orders->fetchAll(PDO::FETCH_ASSOC);

$documents = $pdo->prepare('SELECT d.*, u.full_name AS uploaded_by_name FROM documents d LEFT JOIN users u ON u.id = d.uploaded_by WHERE d.case_id = ? ORDER BY d.uploaded_at DESC');
$documents->execute([$id]);
$documents = $documents->fetchAll(PDO::FETCH_ASSOC);

$title = 'Case View';
include __DIR__ . '/../includes/header.php';
?>
<section class="page-head">
    <div>
        <span class="page-kicker"><?= e($case['case_number']) ?></span>
        <h1><?= e($case['title']) ?></h1>
    </div>
    <div class="head-actions">
        <a class="btn btn-secondary" href="<?= e(BASE_URL . '/party_add.php?case_id=' . $case['id']) ?>">Add Party</a>
        <a class="btn btn-secondary" href="<?= e(BASE_URL . '/hearing_create.php?case_id=' . $case['id']) ?>">Add Hearing</a>
        <a class="btn btn-secondary" href="<?= e(BASE_URL . '/order_create.php?case_id=' . $case['id']) ?>">Add Order</a>
        <a class="btn btn-secondary" href="<?= e(BASE_URL . '/document_upload.php?case_id=' . $case['id']) ?>">Upload Document</a>
    </div>
</section>

<?= flash_message() ?>

<section class="case-view-grid">
    <article class="panel detail-panel">
        <div class="panel-head">
            <h2>Case details</h2>
        </div>
        <div class="detail-list">
            <div><span class="detail-label">Case Number</span><span><?= e($case['case_number']) ?></span></div>
            <div><span class="detail-label">Case Type</span><span><?= e($case['case_type']) ?></span></div>
            <div><span class="detail-label">Status</span><span class="badge badge-<?= e(strtolower($case['status'])) ?>"><?= e($case['status']) ?></span></div>
            <div><span class="detail-label">Filing Date</span><span><?= e(format_date($case['filing_date'])) ?></span></div>
            <div><span class="detail-label">Assigned Judge</span><span><?= e($case['judge_name'] ?: 'Unassigned') ?></span></div>
            <div><span class="detail-label">Created By</span><span><?= e($case['created_by_name'] ?: 'System') ?></span></div>
        </div>
        <div class="case-description">
            <h3>Description</h3>
            <p><?= e($case['description']) ?></p>
        </div>
    </article>

    <article class="panel">
        <div class="panel-head">
            <h2>Parties</h2>
        </div>
        <div class="simple-list">
            <?php foreach ($parties as $party): ?>
                <div class="simple-list-item">
                    <span><?= e($party['name']) ?> (<?= e($party['party_type']) ?>)</span>
                    <span><?= e($party['advocate_name'] ?: 'No advocate') ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<section class="case-tabs">
    <article class="panel">
        <div class="panel-head">
            <h2>Hearings</h2>
        </div>
        <div class="simple-list">
            <?php foreach ($hearings as $h): ?>
                <div class="simple-list-item">
                    <span><?= e(format_date($h['hearing_date'])) ?> <?= e($h['hearing_time']) ?> - <?= e($h['Courtroom']) ?></span>
                    <span><?= e($h['judge_name'] ?: 'Court') ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
    <article class="panel">
        <div class="panel-head">
            <h2>Orders</h2>
        </div>
        <div class="simple-list">
            <?php foreach ($orders as $o): ?>
                <div class="simple-list-item">
                    <span><?= e(format_date($o['order_date'])) ?> - <?= e($o['order_type']) ?></span>
                    <span><?= e($o['description']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
    <article class="panel">
        <div class="panel-head">
            <h2>Documents</h2>
        </div>
        <div class="simple-list">
            <?php foreach ($documents as $d): ?>
                <div class="simple-list-item">
                    <span><?= e($d['title']) ?></span>
                    <span><a href="<?= e(BASE_URL . '/../uploads/' . basename($d['file_path'])) ?>">Download</a></span>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
