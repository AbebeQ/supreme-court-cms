<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

function get_pdo()
{
    global $pdo;
    return $pdo;
}

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect($path)
{
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}

function redirect_to_login()
{
    redirect('login.php');
}

function is_logged_in()
{
    return !empty($_SESSION['user_id']);
}

function require_login()
{
    if (!is_logged_in()) {
        redirect_to_login();
    }
}

function current_user()
{
    $pdo = get_pdo();
    if (!is_logged_in()) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id, username, email, full_name, role, phone, address, is_active FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function flash($type, $message)
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function flash_message()
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return '<div class="flash flash-' . e($flash['type']) . '">' . e($flash['message']) . '</div>';
    }

    return '';
}

function email_exists($pdo, $email)
{
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    return $stmt->fetchColumn() !== false;
}

function user_by_username($pdo, $username)
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function asset($path)
{
    return BASE_URL . '/..' . $path;
}

function format_date($date)
{
    if (empty($date)) {
        return '—';
    }

    return date('d M Y', strtotime($date));
}

function current_user_role()
{
    return strtoupper($_SESSION['user_role'] ?? 'LITIGANT');
}

function current_user_full_name()
{
    return $_SESSION['user_name'] ?? '';
}

function allowed_user_roles_for_role($role)
{
    $role = strtoupper($role);

    if (in_array($role, ['ADMIN', 'CLERK'], true)) {
        return ['ADMIN', 'JUDGE', 'CLERK', 'ADVOCATE', 'LITIGANT'];
    }

    if ($role === 'JUDGE') {
        return ['JUDGE', 'ADVOCATE', 'LITIGANT'];
    }

    if ($role === 'ADVOCATE') {
        return ['ADVOCATE', 'LITIGANT'];
    }

    return ['LITIGANT'];
}

function case_visibility_sql_for_role($role)
{
    $role = strtoupper($role);
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $userName = current_user_full_name();

    if (in_array($role, ['ADMIN', 'CLERK'], true)) {
        return [
            'base' => 'SELECT DISTINCT c.*, u.full_name AS judge_name FROM cases c LEFT JOIN users u ON u.id = c.assigned_judge',
            'where' => '',
            'params' => [],
            'join' => '',
        ];
    }

    if ($role === 'JUDGE') {
        return [
            'base' => 'SELECT DISTINCT c.*, u.full_name AS judge_name FROM cases c LEFT JOIN users u ON u.id = c.assigned_judge',
            'where' => ' WHERE c.assigned_judge = ?',
            'params' => [$userId],
            'join' => '',
        ];
    }

    if ($role === 'ADVOCATE') {
        return [
            'base' => 'SELECT DISTINCT c.*, u.full_name AS judge_name FROM cases c LEFT JOIN users u ON u.id = c.assigned_judge LEFT JOIN parties p ON p.case_id = c.id',
            'where' => ' WHERE LOWER(p.advocate_name) = LOWER(?)',
            'params' => [$userName],
            'join' => '',
        ];
    }

    return [
        'base' => 'SELECT DISTINCT c.*, u.full_name AS judge_name FROM cases c LEFT JOIN users u ON u.id = c.assigned_judge LEFT JOIN parties p ON p.case_id = c.id',
        'where' => ' WHERE LOWER(p.name) = LOWER(?)',
        'params' => [$userName],
        'join' => '',
    ];
}

function tables_title($title)
{
    return $title;
}
