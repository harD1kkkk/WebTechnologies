<?php
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$rootPrefix = str_contains($scriptName, '/admin/') ? '../' : '';
$loggedUser = current_user();
$flashMessage = flash();
$pageTitle = $pageTitle ?? 'Task Manager';
?>
<!doctype html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($pageTitle) ?> | Task Manager</title>
    <link rel="stylesheet" href="<?= h($rootPrefix) ?>assets/style.css">
</head>
<body>
<header class="site-header">
    <div class="nav-wrap">
        <a class="brand" href="<?= h($rootPrefix) ?>zavd1.php"><span class="brand-icon">✓</span> Task Manager</a>
        <nav class="main-nav">
            <a href="<?= h($rootPrefix) ?>zavd1.php">Головна</a>
            <a href="<?= h($rootPrefix) ?>zavd2.php">Завдання</a>
            <a href="<?= h($rootPrefix) ?>zavd3.php">Проєкти</a>
            <?php if ($loggedUser): ?>
                <a href="<?= h($rootPrefix) ?>zavd5.php">Мої завдання</a>
            <?php endif; ?>
        </nav>
        <div class="nav-user">
            <?php if ($loggedUser): ?>
                <span class="user-greeting"><?= h($loggedUser['name']) ?></span>
                <?php if (($loggedUser['role'] ?? 'user') === 'admin'): ?>
                    <a class="button button-small button-outline" href="<?= h($rootPrefix) ?>admin/index.php">Адмінпанель</a>
                <?php endif; ?>
                <a class="button button-small button-light" href="<?= h($rootPrefix) ?>logout.php">Вийти</a>
            <?php else: ?>
                <a href="<?= h($rootPrefix) ?>login.php">Увійти</a>
                <a class="button button-small" href="<?= h($rootPrefix) ?>register.php">Реєстрація</a>
            <?php endif; ?>
        </div>
        <button class="mobile-menu" type="button" onclick="document.body.classList.toggle('menu-open')" aria-label="Відкрити меню">☰</button>
    </div>
</header>
<main class="page-wrap">
    <?php if ($flashMessage): ?>
        <div class="notice <?= h($flashMessage['type']) ?>"><?= h($flashMessage['message']) ?></div>
    <?php endif; ?>
