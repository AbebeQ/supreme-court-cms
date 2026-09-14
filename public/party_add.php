<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_login();

$pdo = get_pdo();
$case_id = (int)($_GET['case_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        flash('error', 'Invalid request token.');
        redirect('party_add.php');
    }

    $case_id = (int)($_POST['case_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $advocate_name = trim($_POST['advocate_name'] ?? '');
    $party_type = trim($_POST['party_type'] ?? 'PLAINTIFF');

    if ($case_id && $name !== '') {
        $stmt = $pdo->prepare('INSERT INTO parties (case_id, name, advocate_name, party_type) VALUES (?, ?, ?, ?)');
        $stmt->execute([$case_id, $name, $advocate_name, $party_type]);
        flash('success', 'Party added.');
        redirect('case_view.php?id=' . $case_id);
    }

    flash('error', 'Party name and case are required.');
    redirect('party_add.php');
}

$title = 'Add Party';
include __DIR__ . '/../includes/header.php';
?>
<section class="page-head">
    <div>
        <span class="page-kicker">Registry</span>
        <h1>Add Party</h1>
    </div>
</section>

<?= flash_message() ?>

<section class="panel form-panel">
    <form method="post" class="case-form">
        <?= csrf_field() ?>
        <input type="hidden" name="case_id" value="<?= e($case_id) ?>">
        <div class="form-grid">
            <div class="form-group">
                <label for="name">Party Name</label>
                <input type="text" name="name" id="name" required>
            </div>
            <div class="form-group">
                <label for="party_type">Party Type</label>
                <select name="party_type" id="party_type">
                    <option>PLAINTIFF</option><option>DEFENDANT</option><option>APPELLANT</option><option>RESPONDENT</option><option>WITNESS</option><option>EXPERT</option><option>INTERVENOR</option><option>OTHER</option>
                </select>
            </div>
            <div class="form-group">
                <label for="advocate_name">Advocate Name</label>
                <input type="text" name="advocate_name" id="advocate_name">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Save Party</button>
    </form>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
