<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$pageTitle = 'Проєкти';
$rootPrefix = '';
$projectId = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
$project = null;
$tasks = [];

if ($projectId && $projectId > 0) {
    $stmt = $conn->prepare('SELECT p.*, u.name AS owner_name FROM projects p LEFT JOIN users u ON u.id = p.owner_id WHERE p.id = ?');
    $stmt->bind_param('i', $projectId);
    $stmt->execute();
    $project = $stmt->get_result()->fetch_assoc();
    if (!$project) {
        http_response_code(404);
        flash('Проєкт не знайдено.', 'error');
        redirect('zavd3.php');
    }
    $stmt = $conn->prepare('SELECT t.*, p.name AS project_name, u.name AS user_name FROM tasks t LEFT JOIN projects p ON p.id=t.project_id LEFT JOIN users u ON u.id=t.user_id WHERE t.project_id=? ORDER BY t.created_at DESC, t.id DESC');
    $stmt->bind_param('i', $projectId);
    $stmt->execute();
    $tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $result = $conn->query('SELECT p.*, u.name AS owner_name, (SELECT COUNT(*) FROM tasks t WHERE t.project_id=p.id) AS task_count FROM projects p LEFT JOIN users u ON u.id=p.owner_id ORDER BY p.created_at DESC, p.id DESC');
    $projects = $result->fetch_all(MYSQLI_ASSOC);
}
require 'includes/header.php';
?>
<?php if ($project): ?>
    <div class="breadcrumb"><a href="zavd1.php">Головна</a><span>/</span><a href="zavd3.php">Проєкти</a><span>/</span><span><?= h($project['name']) ?></span></div>
    <section class="project-hero"><div><div class="eyebrow">ПРОЄКТ</div><h1><?= h($project['name']) ?></h1><p><?= nl2br(h($project['description'] ?? 'Опис проєкту не додано.')) ?></p><div class="project-owner">Власник: <strong><?= h($project['owner_name'] ?? 'Не вказано') ?></strong></div></div><?php if (current_user() && current_user()['role'] === 'admin'): ?><a class="button button-outline" href="admin/project_form.php?id=<?= (int)$project['id'] ?>">Редагувати проєкт</a><?php endif; ?></section>
    <section class="section-block"><div class="section-heading"><div><h2>Завдання проєкту</h2><p>Завдання, пов’язані з цим проєктом</p></div></div><?php require 'includes/task_cards.php'; ?></section>
<?php else: ?>
    <section class="page-heading"><div class="eyebrow">ОРГАНІЗАЦІЯ РОБОТИ</div><h1>Проєкти</h1><p>Усі проєкти та пов’язані з ними завдання.</p></section>
    <?php if (current_user() && current_user()['role'] === 'admin'): ?><div class="toolbar-row"><a class="button" href="admin/project_form.php">+ Додати проєкт</a></div><?php endif; ?>
    <?php if (empty($projects)): ?>
        <div class="empty-state"><div class="empty-icon">▦</div><h3>Проєктів поки немає</h3><p>Адміністратор може додати перший проєкт.</p></div>
    <?php else: ?>
        <div class="project-grid">
            <?php foreach ($projects as $item): ?>
                <a class="project-card" href="zavd3.php?id=<?= (int)$item['id'] ?>"><div class="project-card-top"><span class="project-symbol">▦</span><span class="project-arrow">↗</span></div><h2><?= h($item['name']) ?></h2><p><?= h(mb_strimwidth((string)($item['description'] ?? ''), 0, 155, '…', 'UTF-8')) ?></p><div class="project-card-footer"><span>Власник: <?= h($item['owner_name'] ?? 'Не вказано') ?></span><strong><?= (int)$item['task_count'] ?> завд.</strong></div></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
<?php require 'includes/footer.php'; ?>
