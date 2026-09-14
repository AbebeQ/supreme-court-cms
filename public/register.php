<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$pdo = get_pdo();
$canCreateByClerk = is_logged_in() && in_array(strtoupper($_SESSION['user_role'] ?? ''), ['CLERK', 'ADMIN'], true);
$defaultRole = 'LITIGANT';

if (!$canCreateByClerk && is_logged_in()) {
    redirect('dashboard.php');
}

if (!$pdo) {
    flash('error', 'Database unavailable. Configure a working PostgreSQL host and credentials.');
    include __DIR__ . '/../includes/header.php';
    ?>
    <section class="login-wrap">
        <div class="login-card">
            <div class="login-header">
                <span class="login-logo">⚖</span>
                <h1><?= e(APP_NAME) ?></h1>
                <p>Create an account</p>
            </div>
            <div class="flash flash-error">Database unavailable. Configure a working PostgreSQL host and credentials.</div>
            <div class="mini-link-wrap">
                <a class="mini-link" href="<?= e(BASE_URL . '/login.php') ?>">Already have an account? Login</a>
            </div>
        </div>
    </section>
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        flash('error', 'Invalid request token.');
        redirect('register.php');
    }

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $fayda_number = trim($_POST['fayda_number'] ?? '');
    $role = strtoupper(trim($_POST['role'] ?? $defaultRole));

    if (!$canCreateByClerk) {
        $role = $defaultRole;
    }

    if ($username === '' || $email === '' || $password === '' || $full_name === '' || $fayda_number === '') {
        flash('error', 'Username, email, password, full name and Fayda number are required.');
        redirect('register.php');
    }

    if (!in_array($role, ['ADMIN', 'JUDGE', 'CLERK', 'ADVOCATE', 'LITIGANT'], true)) {
        flash('error', 'Invalid role selected.');
        redirect('register.php');
    }

    if (strlen($password) < 6) {
        flash('error', 'Password must be at least 6 characters.');
        redirect('register.php');
    }

    if (email_exists($pdo, $email)) {
        flash('error', 'An account with that email already exists.');
        redirect('register.php');
    }

    $existing = user_by_username($pdo, $username);
    if ($existing) {
        flash('error', 'That username is already taken.');
        redirect('register.php');
    }

    $existingFayda = $pdo->prepare('SELECT id FROM users WHERE fayda_number = ?');
    $existingFayda->execute([$fayda_number]);
    if ($existingFayda->fetchColumn()) {
        flash('error', 'That Fayda number is already registered.');
        redirect('register.php');
    }

    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, full_name, role, phone, address, fayda_number, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, TRUE)');
    $stmt->execute([$username, $email, $password_hash, $full_name, $role, $phone, $address, $fayda_number]);

    if ($canCreateByClerk) {
        flash('success', 'User registered by clerk.');
        redirect('users.php');
    }

    $user = user_by_username($pdo, $username);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['full_name'];

    flash('success', 'Registration successful. Welcome!');
    redirect('dashboard.php');
}

$title = 'Register';
include __DIR__ . '/../includes/header.php';
?>
<section class="login-wrap">
    <div class="login-card">
        <div class="login-header">
            <span class="login-logo">⚖</span>
            <h1><?= e(APP_NAME) ?></h1>
            <p>Create an account</p>
        </div>

        <?= flash_message() ?>

        <form class="login-form" method="post">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autocomplete="email">
            </div>
            <div class="form-group">
                <label for="fayda_number">Fayda Number</label>
                <input type="text" id="fayda_number" name="fayda_number" required autocomplete="off">
            </div>
            <?php if ($canCreateByClerk): ?>
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="LITIGANT">LITIGANT</option>
                        <option value="ADVOCATE">ADVOCATE</option>
                        <option value="CLERK">CLERK</option>
                        <option value="JUDGE">JUDGE</option>
                        <option value="ADMIN">ADMIN</option>
                    </select>
                </div>
            <?php endif; ?>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone">
            </div>
            <div class="form-group">
                <label for="address">Address</label>
                <textarea name="address" id="address" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary">Register</button>
            <div class="mini-link-wrap">
                <a class="mini-link" href="<?= e(BASE_URL . '/login.php') ?>">Already have an account? Login</a>
            </div>
        </form>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
