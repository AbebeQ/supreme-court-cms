<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$pdo = get_pdo();
if ($pdo instanceof SafePDO) {
    flash('error', 'Database unavailable. Configure a working PostgreSQL host, user, and password.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        flash('error', 'Invalid request token.');
        redirect('login.php');
    }

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username !== '' && $password !== '' && login_user($username, $password)) {
        flash('success', 'Welcome back.');
        redirect('dashboard.php');
    }

    flash('error', 'Invalid username or password.');
    redirect('login.php');
}

$title = 'Login';
include __DIR__ . '/../includes/header.php';
?>
<section class="login-wrap">
    <div class="login-card">
        <div class="login-header">
            <span class="login-logo">⚖</span>
            <h1><?= e(APP_NAME) ?></h1>
            <p>Case Management Portal</p>
        </div>

        <?= flash_message() ?>

        <form class="login-form" method="post">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
             <div class="mini-link-wrap">
                <a class="mini-link" href="<?= e(BASE_URL . '/register.php') ?>">Don't have an account? Register</a>
            </div>
        </form>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
