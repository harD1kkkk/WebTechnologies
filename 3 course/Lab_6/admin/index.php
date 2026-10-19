<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_admin();
$pageTitle = 'Адмінпанель';
$stats = [];
foreach (['tasks' => 'Завдань', 'projects' => 'Проєктів', 'users' => 'Користувачів', 'comments' => 'Коментарів'] as $table => $label) {
    $result = $conn->query("SELECT COUNT(*) AS total FROM {$table}");
    $stats[$label] = (int)$result->fetch_assoc()['total'];
}
$tasksResult = $conn->query('SELECT t.id, t.title, t.status, t.priority, p.name AS project_name FROM tasks t LEFT JOIN projects p ON p.id=t.project_id ORDER BY t.created_at DESC, t.id DESC LIMIT 8');
$latestTasks = $tasksResult->fetch_all(MYSQLI_ASSOC);
require '../includes/header.php';
?>
<section class="page-heading admin-heading"><div><div class="eyebrow">КЕРУВАННЯ СИСТЕМОЮ</div><h1>Адміністративна панель</h1><p>Керуй завданнями, проєктами та обліковими записами.</p></div><span class="admin-stamp">ADMIN</span></section>
<section class="stats-grid admin-stats"><?php foreach ($stats as $label => $value): ?><div class="stat-card"><div class="stat-icon">▤</div><div><strong><?= $value ?></strong><span><?= h($label) ?></span></div></div><?php endforeach; ?></section>
<section class="admin-shortcuts"><a class="shortcut-card" href="task_form.php"><span class="shortcut-icon">＋</span><div><h3>Додати завдання</h3><p>Створити новий запис та завантажити фото.</p></div><span class="project-arrow">↗</span></a><a class="shortcut-card" href="project_form.php"><span class="shortcut-icon">▦</span><div><h3>Додати проєкт</h3><p>Створити проєкт і призначити власника.</p></div><span class="project-arrow">↗</span></a><a class="shortcut-card" href="users.php"><span class="shortcut-icon">♙</span><div><h3>Користувачі</h3><p>Перегляд облікових записів і ролей.</p></div><span class="project-arrow">↗</span></a></section>
<section class="section-block"><div class="section-heading"><div><h2>Останні завдання</h2><p>Швидкий доступ до редагування записів</p></div><a class="button button-outline" href="../zavd2.php">Переглянути сайт →</a></div>
    <?php if ($latestTasks): ?><div class="table-wrap"><table class="data-table"><thead><tr><th>Назва</th><th>Проєкт</th><th>Статус</th><th>Пріоритет</th><th>Дії</th></tr></thead><tbody><?php foreach ($latestTasks as $task): ?><tr><td><strong><?= h($task['title']) ?></strong></td><td><?= h($task['project_name'] ?? '—') ?></td><td><span class="status-pill <?= h(status_class($task['status'])) ?>"><?= h($task['status']) ?></span></td><td><?= h($task['priority']) ?></td><td><a class="table-link" href="task_form.php?id=<?= (int)$task['id'] ?>">Редагувати</a></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="empty-state"><h3>Завдань ще немає</h3><a class="button" href="task_form.php">Додати завдання</a></div><?php endif; ?>
</section>
<?php require '../includes/footer.php'; ?>
