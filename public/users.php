<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo = get_pdo();
$viewerRole = current_user_role();
$viewerId = (int)($_SESSION['user_id'] ?? 0);
$viewerName = current_user_full_name();
$allowedRoles = allowed_user_roles_for_role($viewerRole);

if (in_array($viewerRole, ['ADMIN', 'CLERK'], true)) {
    $users = $pdo->query('SELECT id, username, email, full_name, role, phone, is_active FROM users ORDER BY full_name')->fetchAll(PDO::FETCH_ASSOC);
} elseif ($viewerRole === 'JUDGE') {
    $placeholders = implode(',', array_fill(0, count($allowedRoles), '?'));
    $stmt = $pdo->prepare('SELECT id, username, email, full_name, role, phone, is_active FROM users WHERE role IN (' . $placeholders . ') ORDER BY full_name');
    $stmt->execute($allowedRoles);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($viewerRole === 'ADVOCATE') {
    $placeholders = implode(',', array_fill(0, count($allowedRoles), '?'));
    $stmt = $pdo->prepare('SELECT id, username, email, full_name, role, phone, is_active FROM users WHERE role IN (' . $placeholders . ') ORDER BY full_name');
    $stmt->execute($allowedRoles);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare('SELECT id, username, email, full_name, role, phone, is_active FROM users WHERE id = ? ORDER BY full_name');
    $stmt->execute([$viewerId]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$title = 'Users';
include __DIR__ . '/../includes/header.php';
?>
<section class="page-head">
    <div>
        <span class="page-kicker">Directory</span>
        <h1>Users</h1>
    </div>
</section>

<?= flash_message() ?>

<section class="panel">
    <div class="panel-head">
        <h2>System Users</h2>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Role</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= e($user['full_name']) ?></td>
                    <td><?= e($user['username']) ?></td>
                    <td><?= e($user['email']) ?></td>
                    <td><?= e($user['phone']) ?></td>
                    <td><?= e($user['role']) ?></td>
                    <td><?= $user['is_active'] ? 'Active' : 'Inactive' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
