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
        redirect('hearing_create.php');
    }

    $case_id = (int)($_POST['case_id'] ?? 0);
    $hearing_date = trim($_POST['hearing_date'] ?? date('Y-m-d'));
    $hearing_time = trim($_POST['hearing_time'] ?? '09:00:00');
    $courtroom = trim($_POST['courtroom'] ?? 'Main Court');
    $hearing_type = trim($_POST['hearing_type'] ?? 'Regular');
    $notes = trim($_POST['notes'] ?? '');
    $judge_id = (int)($_POST['judge_id'] ?? 0);

    if ($case_id && $hearing_date) {
        $stmt = $pdo->prepare('INSERT INTO hearings (case_id, hearing_date, hearing_time, courtroom, hearing_type, judge_id, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$case_id, $hearing_date . ' 00:00:00', $hearing_time, $courtroom, $hearing_type, $judge_id ?: null, $notes, $_SESSION['user_id']]);
        flash('success', 'Hearing scheduled.');
        redirect('case_view.php?id=' . $case_id);
    }

    flash('error', 'A valid case and date are required.');
    redirect('hearing_create.php');
}

$judges = $pdo->query('SELECT id, full_name FROM users WHERE role = \'JUDGE\' ORDER BY full_name')->fetchAll(PDO::FETCH_ASSOC);
$title = 'Create Hearing';
include __DIR__ . '/../includes/header.php';
?>
<section class="page-head">
    <div>
        <span class="page-kicker">Scheduling</span>
        <h1>Create Hearing</h1>
    </div>
</section>

<?= flash_message() ?>

<section class="panel form-panel">
    <form method="post" class="case-form">
        <?= csrf_field() ?>
        <input type="hidden" name="case_id" value="<?= e($case_id) ?>">
        <div class="form-grid">
            <div class="form-group">
                <label for="hearing_date">Hearing Date</label>
                <input type="date" name="hearing_date" id="hearing_date" required>
            </div>
            <div class="form-group">
                <label for="hearing_time">Hearing Time</label>
                <input type="time" name="hearing_time" id="hearing_time" value="09:00">
            </div>
            <div class="form-group">
                <label for="courtroom">Courtroom</label>
                <input type="text" name="courtroom" id="courtroom" value="Main Court">
            </div>
            <div class="form-group">
                <label for="hearing_type">Hearing Type</label>
                <input type="text" name="hearing_type" id="hearing_type" value="Regular">
            </div>
            <div class="form-group">
                <label for="judge_id">Judge</label>
                <select name="judge_id" id="judge_id">
                    <option value="">Unassigned</option>
                    <?php foreach ($judges as $judge): ?>
                        <option value="<?= e($judge['id']) ?>"><?= e($judge['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea name="notes" id="notes" rows="4"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Schedule Hearing</button>
    </form>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
