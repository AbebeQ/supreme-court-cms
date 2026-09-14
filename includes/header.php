<?php
require_once __DIR__ . '/functions.php';

$title = $title ?? APP_NAME;
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="<?= e(BASE_URL . '/../assets/css/style.css') ?>">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">
            <a class="brand-link" href="<?= e(BASE_URL . '/dashboard.php') ?>">
                <span class="brand-icon">⚖</span>
                <span class="brand-text"><?= e(APP_NAME) ?></span>
            </a>
        </div>

        <nav class="nav">
            <a class="nav-link" href="<?= e(BASE_URL . '/dashboard.php') ?>">Dashboard</a>
            <a class="nav-link" href="<?= e(BASE_URL . '/cases.php') ?>">Cases</a>
            <a class="nav-link" href="<?= e(BASE_URL . '/case_create.php') ?>">Create Case</a>
            <a class="nav-link" href="<?= e(BASE_URL . '/party_add.php') ?>">Parties</a>
            <a class="nav-link" href="<?= e(BASE_URL . '/hearing_create.php') ?>">Hearings</a>
            <a class="nav-link" href="<?= e(BASE_URL . '/order_create.php') ?>">Orders</a>
            <a class="nav-link" href="<?= e(BASE_URL . '/document_upload.php') ?>">Documents</a>
            <a class="nav-link" href="<?= e(BASE_URL . '/users.php') ?>">Users</a>
        </nav>

        <?php if ($user): ?>
            <div class="user-card">
                <div class="user-card-name"><?= e($user['full_name']) ?></div>
                <div class="user-card-role"><?= e(strtoupper($user['role'])) ?></div>
                <a class="logout" href="<?= e(BASE_URL . '/logout.php') ?>">Logout</a>
            </div>
        <?php endif; ?>
    </aside>

    <main class="main-content">
