<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/image_upload.php';
require_admin();
$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
$id = $id && $id > 0 ? $id : null;
$pageTitle = $id ? 'Редагування завдання' : 'Нове завдання';
$statuses = ['Нове', 'В процесі', 'Завершено'];
$priorities = ['Низький', 'Середній', 'Високий'];
$users = $conn->query('SELECT id, name, email FROM users ORDER BY name')->fetch_all(MYSQLI_ASSOC);
$projects = $conn->query('SELECT id, name FROM projects ORDER BY name')->fetch_all(MYSQLI_ASSOC);
$task = ['title' => '', 'description' => '', 'status' => 'Нове', 'priority' => 'Середній', 'user_id' => '', 'project_id' => '', 'due_date' => '', 'image_path' => null, 'thumbnail_path' => null];
$error = '';

if ($id) {
    $stmt = $conn->prepare('SELECT * FROM tasks WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    if (!$found) {
        flash('Завдання не знайдено.', 'error');
        redirect('index.php');
    }
    $task = array_merge($task, $found);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid()) {
        $error = 'Форма застаріла. Оновіть сторінку та спробуйте ще раз.';
    } else {
        $task['title'] = trim((string)($_POST['title'] ?? ''));
        $task['description'] = trim((string)($_POST['description'] ?? ''));
        $task['status'] = (string)($_POST['status'] ?? 'Нове');
        $task['priority'] = (string)($_POST['priority'] ?? 'Середній');
        $task['user_id'] = (int)($_POST['user_id'] ?? 0);
        $task['project_id'] = (int)($_POST['project_id'] ?? 0);
        $task['due_date'] = trim((string)($_POST['due_date'] ?? ''));

        $userIds = array_map(fn($row) => (int)$row['id'], $users);
        $projectIds = array_map(fn($row) => (int)$row['id'], $projects);
        if ($task['title'] === '' || mb_strlen($task['title']) > 200) {
            $error = 'Назва завдання обов’язкова та має містити не більше 200 символів.';
        } elseif (!in_array($task['status'], $statuses, true) || !in_array($task['priority'], $priorities, true)) {
            $error = 'Виберіть коректні статус і пріоритет.';
        } elseif (!in_array((int)$task['user_id'], $userIds, true) || !in_array((int)$task['project_id'], $projectIds, true)) {
            $error = 'Виберіть наявного користувача та проєкт.';
        } elseif ($task['due_date'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $task['due_date'])) {
            $error = 'Вкажіть коректну дату виконання.';
        } else {
            $newImage = null;
            try {
                if (isset($_FILES['image'])) {
                    $newImage = create_task_image($_FILES['image']);
                }
            } catch (RuntimeException $exception) {
                $error = $exception->getMessage();
            }

            if (!$error) {
                $imagePath = $newImage['image_path'] ?? $task['image_path'];
                $thumbPath = $newImage['thumbnail_path'] ?? $task['thumbnail_path'];
                $dueDate = $task['due_date'] === '' ? null : $task['due_date'];
                if ($id) {
                    $stmt = $conn->prepare('UPDATE tasks SET title=?, description=?, status=?, priority=?, user_id=?, project_id=?, due_date=?, image_path=?, thumbnail_path=? WHERE id=?');
                    $types = 'ssssii' . 'sssi';
                    $params = [$task['title'], $task['description'], $task['status'], $task['priority'], (int)$task['user_id'], (int)$task['project_id'], $dueDate, $imagePath, $thumbPath, $id];
                    bind_params($stmt, $types, $params);
                    $saved = $stmt->execute();
                    if ($saved && $newImage) {
                        delete_task_images($task['image_path'], $task['thumbnail_path']);
                    }
                } else {
                    $stmt = $conn->prepare('INSERT INTO tasks (title, description, status, priority, user_id, project_id, due_date, image_path, thumbnail_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $types = 'ssssii' . 'sss';
                    $params = [$task['title'], $task['description'], $task['status'], $task['priority'], (int)$task['user_id'], (int)$task['project_id'], $dueDate, $imagePath, $thumbPath];
                    bind_params($stmt, $types, $params);
                    $saved = $stmt->execute();
                }

                if ($saved) {
                    flash($id ? 'Завдання оновлено.' : 'Завдання створено.');
                    redirect('../zavd4.php?id=' . ($id ?: $stmt->insert_id));
                }
                if ($newImage) {
                    delete_task_images($newImage['image_path'], $newImage['thumbnail_path']);
                }
                $error = 'Не вдалося зберегти завдання. Перевірте дані та повторіть спробу.';
            }
        }
    }
}
require '../includes/header.php';
?>
<div class="breadcrumb"><a href="index.php">Адмінпанель</a><span>/</span><span><?= $id ? 'Редагування завдання' : 'Нове завдання' ?></span></div>
<section class="form-page"><div class="page-heading"><div class="eyebrow">КЕРУВАННЯ ЗАВДАННЯМИ</div><h1><?= $id ? 'Редагувати завдання' : 'Створити завдання' ?></h1></div>
    <div class="admin-form-card">
        <?php if ($error): ?><div class="notice error"><?= h($error) ?></div><?php endif; ?>
        <?php if (!$users || !$projects): ?><div class="notice error">Для створення завдання потрібні користувачі та проєкти. Спочатку <a href="users.php">перевірте користувачів</a> і <a href="project_form.php">створіть проєкт</a>.</div><?php endif; ?>
        <form method="post" enctype="multipart/form-data" class="form-grid"><?php csrf_field(); ?>
            <label class="full-field">Назва завдання<input type="text" name="title" maxlength="200" required value="<?= h($task['title']) ?>"></label>
            <label class="full-field">Опис<textarea name="description" rows="5" placeholder="Опишіть завдання..."><?= h($task['description']) ?></textarea></label>
            <label>Статус<select name="status"><?php foreach ($statuses as $option): ?><option value="<?= h($option) ?>" <?= $task['status'] === $option ? 'selected' : '' ?>><?= h($option) ?></option><?php endforeach; ?></select></label>
            <label>Пріоритет<select name="priority"><?php foreach ($priorities as $option): ?><option value="<?= h($option) ?>" <?= $task['priority'] === $option ? 'selected' : '' ?>><?= h($option) ?></option><?php endforeach; ?></select></label>
            <label>Відповідальний користувач<select name="user_id" required><?php foreach ($users as $item): ?><option value="<?= (int)$item['id'] ?>" <?= (int)$task['user_id'] === (int)$item['id'] ? 'selected' : '' ?>><?= h($item['name']) ?> (<?= h($item['email']) ?>)</option><?php endforeach; ?></select></label>
            <label>Проєкт<select name="project_id" required><?php foreach ($projects as $item): ?><option value="<?= (int)$item['id'] ?>" <?= (int)$task['project_id'] === (int)$item['id'] ? 'selected' : '' ?>><?= h($item['name']) ?></option><?php endforeach; ?></select></label>
            <label>Термін виконання<input type="date" name="due_date" value="<?= h($task['due_date']) ?>"></label>
            <label>Фотографія (JPG, PNG, WebP)<input type="file" name="image" accept="image/jpeg,image/png,image/webp"><small>Максимум 5 МБ. Для фото автоматично створюється мініатюра.</small></label>
            <?php if (!empty($task['thumbnail_path']) && is_file(dirname(__DIR__) . '/' . $task['thumbnail_path'])): ?><div class="current-image full-field"><span>Поточне фото</span><img src="../<?= h($task['thumbnail_path']) ?>" alt="Поточна фотографія"></div><?php endif; ?>
            <div class="form-actions full-field"><button class="button" type="submit" <?= (!$users || !$projects) ? 'disabled' : '' ?>><?= $id ? 'Зберегти зміни' : 'Створити завдання' ?></button><a class="button button-light" href="index.php">Скасувати</a><?php if ($id): ?><a class="table-link" href="../zavd4.php?id=<?= $id ?>">Переглянути завдання ↗</a><?php endif; ?></div>
        </form>
    </div>
</section>
<?php require '../includes/footer.php'; ?>
