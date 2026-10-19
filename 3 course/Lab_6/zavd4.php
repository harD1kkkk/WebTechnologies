<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$pageTitle = 'Деталі завдання';
$taskId = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
if (!$taskId || $taskId < 1) {
    flash('Не вказано правильний номер завдання.', 'error');
    redirect('zavd2.php');
}

$stmt = $conn->prepare('SELECT t.*, p.name AS project_name, u.name AS user_name FROM tasks t LEFT JOIN projects p ON p.id=t.project_id LEFT JOIN users u ON u.id=t.user_id WHERE t.id=?');
$stmt->bind_param('i', $taskId);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();
if (!$task) {
    http_response_code(404);
    $pageTitle = 'Завдання не знайдено';
    require 'includes/header.php';
    echo '<div class="empty-state"><h2>Завдання не знайдено</h2><a class="button" href="zavd2.php">До списку завдань</a></div>';
    require 'includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    if (!csrf_is_valid()) {
        flash('Форма застаріла. Спробуйте ще раз.', 'error');
        redirect('zavd4.php?id=' . $taskId);
    }
    $comment = trim((string)($_POST['comment'] ?? ''));
    if ($comment === '' || mb_strlen($comment) > 2000) {
        flash('Коментар має містити від 1 до 2000 символів.', 'error');
    } else {
        $user = current_user();
        $stmt = $conn->prepare('INSERT INTO comments (task_id, user_id, comment) VALUES (?, ?, ?)');
        $stmt->bind_param('iis', $taskId, $user['id'], $comment);
        if ($stmt->execute()) {
            flash('Коментар додано.');
        } else {
            flash('Не вдалося додати коментар.', 'error');
        }
    }
    redirect('zavd4.php?id=' . $taskId . '#comments');
}

$stmt = $conn->prepare('SELECT c.*, u.name AS user_name FROM comments c LEFT JOIN users u ON u.id=c.user_id WHERE c.task_id=? ORDER BY c.created_at DESC, c.id DESC');
$stmt->bind_param('i', $taskId);
$stmt->execute();
$comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
require 'includes/header.php';
?>
<div class="breadcrumb"><a href="zavd1.php">Головна</a><span>/</span><a href="zavd2.php">Завдання</a><span>/</span><span>Деталі</span></div>
<section class="detail-layout">
    <article class="detail-main">
        <div class="card-meta"><span class="status-pill <?= h(status_class($task['status'])) ?>"><?= h($task['status']) ?></span><span class="priority priority-<?= $task['priority'] === 'Високий' ? 'high' : ($task['priority'] === 'Низький' ? 'low' : 'medium') ?>"><?= h($task['priority']) ?> пріоритет</span></div>
        <h1><?= h($task['title']) ?></h1>
        <?php if (!empty($task['image_path']) && is_file(__DIR__ . '/' . $task['image_path'])): ?><a class="detail-image" href="<?= h($task['image_path']) ?>" target="_blank" rel="noopener"><img src="<?= h($task['thumbnail_path'] ?: $task['image_path']) ?>" alt="Фото завдання"></a><?php endif; ?>
        <div class="detail-description"><?= $task['description'] ? nl2br(h($task['description'])) : '<p class="muted">Опис завдання не додано.</p>' ?></div>
        <?php if (current_user() && current_user()['role'] === 'admin'): ?>
            <div class="detail-actions"><a class="button" href="admin/task_form.php?id=<?= (int)$task['id'] ?>">Редагувати завдання</a>
                <form method="post" action="admin/task_delete.php" onsubmit="return confirm('Видалити це завдання?');"><?php csrf_field(); ?><input type="hidden" name="id" value="<?= (int)$task['id'] ?>"><button class="button button-danger" type="submit">Видалити</button></form>
            </div>
        <?php endif; ?>
        <section class="comments-section" id="comments"><div class="section-heading"><div><h2>Коментарі</h2><p><?= count($comments) ?> повідомлень</p></div></div>
            <?php if (current_user()): ?>
                <form class="comment-form" method="post"><?php csrf_field(); ?><label for="comment">Залишити коментар</label><textarea id="comment" name="comment" rows="3" maxlength="2000" required placeholder="Напишіть коментар до завдання..."></textarea><button class="button" type="submit">Додати коментар</button></form>
            <?php else: ?><div class="login-hint"><a href="login.php">Увійдіть</a>, щоб залишити коментар.</div><?php endif; ?>
            <?php if ($comments): ?>
                <div class="comment-list"><?php foreach ($comments as $comment): ?><article class="comment"><div class="comment-avatar"><?= h(mb_strtoupper(mb_substr((string)($comment['user_name'] ?? '?'), 0, 1, 'UTF-8'), 'UTF-8')) ?></div><div class="comment-body"><div class="comment-heading"><strong><?= h($comment['user_name'] ?? 'Користувач') ?></strong><time><?= h(date('d.m.Y H:i', strtotime($comment['created_at']))) ?></time></div><p><?= nl2br(h($comment['comment'])) ?></p></div></article><?php endforeach; ?></div>
            <?php else: ?><p class="muted">Поки що коментарів немає.</p><?php endif; ?>
        </section>
    </article>
    <aside class="detail-sidebar"><div class="sidebar-card"><h3>Інформація</h3><div class="info-row"><span>Проєкт</span><strong><?= h($task['project_name'] ?? 'Не вказано') ?></strong></div><div class="info-row"><span>Відповідальний</span><strong><?= h($task['user_name'] ?? 'Не вказано') ?></strong></div><div class="info-row"><span>Термін виконання</span><strong><?= h(format_date($task['due_date'])) ?></strong></div><div class="info-row"><span>Створено</span><strong><?= h(format_date($task['created_at'])) ?></strong></div></div><a class="back-link" href="zavd2.php">← Повернутися до списку</a></aside>
</section>
<?php require 'includes/footer.php'; ?>
