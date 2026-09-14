<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_login();

$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        flash('error', 'Invalid request token.');
        redirect('case_create.php');
    }

    $case_number = trim($_POST['case_number'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $case_type = trim($_POST['case_type'] ?? 'CIVIL');
    $status = trim($_POST['status'] ?? 'FILED');
    $filing_date = trim($_POST['filing_date'] ?? date('Y-m-d'));

    if ($case_number === '' || $title === '') {
        flash('error', 'Case number and title are required.');
        redirect('case_create.php');
    }

    $stmt = $pdo->prepare('INSERT INTO cases (case_number, title, description, case_type, status, filing_date, created_by, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$case_number, $title, $description, $case_type, $status, $filing_date, $_SESSION['user_id'], $_SESSION['user_id']]);

    flash('success', 'Case created successfully.');
    redirect('cases.php');
}

$judges = $pdo->query('SELECT id, full_name FROM users WHERE role IN (\'JUDGE\', \'CLERK\') ORDER BY full_name')->fetchAll(PDO::FETCH_ASSOC);
$title = 'Create Case';
include __DIR__ . '/../includes/header.php';
?>
<section class="page-head">
    <div>
        <span class="page-kicker">Registry</span>
        <h1>Create Case</h1>
    </div>
</section>

<?= flash_message() ?>

<section class="panel form-panel">
    <form method="post" class="case-form">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="form-group">
                <label for="case_number">Case Number</label>
                <input type="text" name="case_number" id="case_number" required>
            </div>
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" name="title" id="title" required>
            </div>
            <div class="form-group">
                <label for="case_type">Case Type</label>
                <select name="case_type" id="case_type">
                    <option>CIVIL</option><option>CRIMINAL</option><option>FAMILY</option><option>LABOR</option><option>COMMERCIAL</option><option>CONSTITUTIONAL</option><option>ADMINISTRATIVE</option><option>ENVIRONMENTAL</option><option>INTELLECTUAL_PROPERTY</option><option>TAX</option><option>BANKRUPTCY</option><option>MARITIME</option><option>INTERNATIONAL</option><option>APPEAL</option><option>WRIT</option><option>OTHER</option>
                </select>
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" id="status">
                    <option>FILED</option><option>HEARING</option><option>PENDING</option><option>JUDGMENT</option><option>CLOSED</option><option>APPEALED</option>
                </select>
            </div>
            <div class="form-group">
                <label for="filing_date">Filing Date</label>
                <input type="date" name="filing_date" id="filing_date" value="<?= e(date('Y-m-d')) ?>">
            </div>
            <div class="form-group">
                <label for="assigned_judge">Assigned Judge</label>
                <select name="assigned_judge" id="assigned_judge">
                    <option value="">Unassigned</option>
                    <?php foreach ($judges as $judge): ?>
                        <option value="<?= e($judge['id']) ?>"><?= e($judge['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" rows="5"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Create Case</button>
    </form>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
