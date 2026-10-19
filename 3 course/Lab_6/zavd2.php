<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$pageTitle = 'Завдання';
$rootPrefix = '';

$search = trim((string)($_GET['q'] ?? ''));
$status = (string)($_GET['status'] ?? '');
$priority = (string)($_GET['priority'] ?? '');
$projectId = filter_var($_GET['project_id'] ?? '', FILTER_VALIDATE_INT);
$sort = (string)($_GET['sort'] ?? 'created_at');
$direction = strtolower((string)($_GET['direction'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
$statuses = ['Нове', 'В процесі', 'Завершено'];
$priorities = ['Низький', 'Середній', 'Високий'];
$sortFields = [
    'created_at' => 't.created_at',
    'title' => 't.title',
    'due_date' => 't.due_date',
    'priority' => "FIELD(t.priority, 'Низький', 'Середній', 'Високий')"
];
if (!isset($sortFields[$sort])) {
    $sort = 'created_at';
}

$where = [];
$params = [];
$types = '';
if ($search !== '') {
    $where[] = '(t.title LIKE ? OR t.description LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}
if (in_array($status, $statuses, true)) {
    $where[] = 't.status = ?';
    $params[] = $status;
    $types .= 's';
} else {
    $status = '';
}
if (in_array($priority, $priorities, true)) {
    $where[] = 't.priority = ?';
    $params[] = $priority;
    $types .= 's';
} else {
    $priority = '';
}
if ($projectId && $projectId > 0) {
    $where[] = 't.project_id = ?';
    $params[] = $projectId;
    $types .= 'i';
} else {
    $projectId = null;
}
$sql = "SELECT t.*, p.name AS project_name, u.name AS user_name
        FROM tasks t
        LEFT JOIN projects p ON p.id = t.project_id
        LEFT JOIN users u ON u.id = t.user_id";
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY ' . $sortFields[$sort] . ' ' . $direction . ', t.id DESC';
$stmt = $conn->prepare($sql);
bind_params($stmt, $types, $params);
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$projects = $conn->query('SELECT id, name FROM projects ORDER BY name')->fetch_all(MYSQLI_ASSOC);
require 'includes/header.php';
?>
<section class="page-heading"><div class="eyebrow">ПЛАНУВАННЯ</div><h1>Усі завдання</h1><p>Знаходьте завдання за назвою, проєктом, статусом і пріоритетом.</p></section>
<section class="filter-panel">
    <form method="get" class="filter-form">
        <label class="filter-search"><span>Пошук</span><input type="search" name="q" value="<?= h($search) ?>" placeholder="Назва або опис завдання"></label>
        <label><span>Статус</span><select name="status"><option value="">Усі статуси</option><?php foreach ($statuses as $item): ?><option value="<?= h($item) ?>" <?= $status === $item ? 'selected' : '' ?>><?= h($item) ?></option><?php endforeach; ?></select></label>
        <label><span>Пріоритет</span><select name="priority"><option value="">Усі пріоритети</option><?php foreach ($priorities as $item): ?><option value="<?= h($item) ?>" <?= $priority === $item ? 'selected' : '' ?>><?= h($item) ?></option><?php endforeach; ?></select></label>
        <label><span>Проєкт</span><select name="project_id"><option value="">Усі проєкти</option><?php foreach ($projects as $project): ?><option value="<?= (int)$project['id'] ?>" <?= $projectId === (int)$project['id'] ? 'selected' : '' ?>><?= h($project['name']) ?></option><?php endforeach; ?></select></label>
        <label><span>Сортувати за</span><select name="sort"><option value="created_at" <?= $sort === 'created_at' ? 'selected' : '' ?>>Датою створення</option><option value="title" <?= $sort === 'title' ? 'selected' : '' ?>>Назвою</option><option value="due_date" <?= $sort === 'due_date' ? 'selected' : '' ?>>Терміном</option><option value="priority" <?= $sort === 'priority' ? 'selected' : '' ?>>Пріоритетом</option></select></label>
        <label><span>Порядок</span><select name="direction"><option value="desc" <?= $direction === 'DESC' ? 'selected' : '' ?>>За спаданням</option><option value="asc" <?= $direction === 'ASC' ? 'selected' : '' ?>>За зростанням</option></select></label>
        <div class="filter-actions"><button class="button" type="submit">Застосувати</button><a class="button button-light" href="zavd2.php">Скинути</a></div>
    </form>
</section>
<div class="results-line"><span>Знайдено: <strong><?= count($tasks) ?></strong></span><?php if (current_user() && current_user()['role'] === 'admin'): ?><a class="button button-small" href="admin/task_form.php">+ Додати завдання</a><?php endif; ?></div>
<?php require 'includes/task_cards.php'; ?>
<?php require 'includes/footer.php'; ?>
