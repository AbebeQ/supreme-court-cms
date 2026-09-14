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
        redirect('document_upload.php');
    }

    $case_id = (int)($_POST['case_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $is_public = isset($_POST['is_public']) && $_POST['is_public'] === 'on';

    if ($case_id && isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $targetDir = UPLOAD_DIR;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', basename($_FILES['file']['name']));
        $targetFile = $targetDir . $filename;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
            $stmt = $pdo->prepare('INSERT INTO documents (case_id, title, description, file_path, uploaded_by, is_public) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bindValue(1, $case_id, PDO::PARAM_INT);
            $stmt->bindValue(2, $title ?: basename($_FILES['file']['name']), PDO::PARAM_STR);
            $stmt->bindValue(3, $description, PDO::PARAM_STR);
            $stmt->bindValue(4, $filename, PDO::PARAM_STR);
            $stmt->bindValue(5, $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindValue(6, $is_public, PDO::PARAM_BOOL);
            $stmt->execute();
            flash('success', 'Document uploaded.');
            redirect('case_view.php?id=' . $case_id);
        }
    }

    flash('error', 'Unable to upload document.');
    redirect('document_upload.php');
}

$title = 'Upload Document';
include __DIR__ . '/../includes/header.php';
?>
<section class="page-head">
    <div>
        <span class="page-kicker">Documents</span>
        <h1>Upload Document</h1>
    </div>
</section>

<?= flash_message() ?>

<section class="panel form-panel">
    <form method="post" enctype="multipart/form-data" class="case-form">
        <?= csrf_field() ?>
        <input type="hidden" name="case_id" value="<?= e($case_id) ?>">
        <div class="form-grid">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" name="title" id="title" required>
            </div>
            <div class="form-group">
                <label for="file">Document</label>
                <input type="file" name="file" id="file" required>
            </div>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" rows="4"></textarea>
        </div>
        <div class="form-group checkbox-row">
            <label for="is_public">
                <input type="checkbox" name="is_public" id="is_public" value="on">
                Make this document public
            </label>
        </div>
        <button type="submit" class="btn btn-primary">Upload</button>
    </form>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
