<?php
require_once __DIR__ . '/functions.php';

function login_user($username, $password)
{
    $pdo = get_pdo();
    $user = user_by_username($pdo, $username);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    if (!$user['is_active']) {
        flash('error', 'The account is inactive. Contact an administrator.');
        return false;
    }

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['full_name'];

    return true;
}

function logout_user()
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function require_role($role)
{
    require_login();
    $user = current_user();

    if (!$user || strtoupper($user['role']) !== strtoupper($role)) {
        flash('error', 'You do not have permission to view this page.');
        redirect('dashboard.php');
    }
}
