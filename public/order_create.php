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
        redirect('order_create.php');
    }

    $case_id = (int)($_POST['case_id'] ?? 0);
    $order_date = trim($_POST['order_date'] ?? date('Y-m-d'));
    $order_type = trim($_POST['order_type'] ?? 'General');
    $description = trim($_POST['description'] ?? '');

    if ($case_id && $description !== '') {
        $stmt = $pdo->prepare('INSERT INTO orders (case_id, order_date, order_type, description, issued_by) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$case_id, $order_date . ' 00:00:00', $order_type, $description, $_SESSION['user_id']]);
        flash('success', 'Order recorded.');
        redirect('case_view.php?id=' . $case_id);
    }

    flash('error', 'Case and description are required.');
    redirect('order_create.php');
}

$title = 'Create Order';
include __DIR__ . '/../includes/header.php';
?>
<section class="page-head">
    <div>
        <span class="page-kicker">Orders</span>
        <h1>Create Order</h1>
    </div>
</section>

<?= flash_message() ?>

<section class="panel form-panel">
    <form method="post" class="case-form">
        <?= csrf_field() ?>
        <input type="hidden" name="case_id" value="<?= e($case_id) ?>">
        <div class="form-grid">
            <div class="form-group">
                <label for="order_date">Order Date</label>
                <input type="date" name="order_date" id="order_date" value="<?= e(date('Y-m-d')) ?>">
            </div>
            <div class="form-group">
                <label for="order_type">Order Type</label>
                <input type="text" name="order_type" id="order_type" value="General">
            </div>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" rows="5" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Save Order</button>
    </form>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
