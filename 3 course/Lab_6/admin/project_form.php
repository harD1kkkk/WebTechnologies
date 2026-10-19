<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_admin();
$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
$id = $id && $id > 0 ? $id : null;
$pageTitle = $id ? 'Редагування проєкту' : 'Новий проєкт';
$users = $conn->query('SELECT id, name, email FROM users ORDER BY name')->fetch_all(MYSQLI_ASSOC);
$project = ['name' => '', 'description' => '', 'owner_id' => ''];
$error = '';
if ($id) {
    $stmt = $conn->prepare('SELECT * FROM projects WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    if (!$found) {
        flash('Проєкт не знайдено.', 'error');
        redirect('index.php');
    }
    $project = array_merge($project, $found);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid()) {
        $error = 'Форма застаріла. Оновіть сторінку та спробуйте ще раз.';
    } else {
        $project['name'] = trim((string)($_POST['name'] ?? ''));
        $project['description'] = trim((string)($_POST['description'] ?? ''));
        $project['owner_id'] = (int)($_POST['owner_id'] ?? 0);
        $userIds = array_map(fn($row) => (int)$row['id'], $users);
        if ($project['name'] === '' || mb_strlen($project['name']) > 150) {
            $error = 'Назва проєкту обов’язкова та має містити не більше 150 символів.';
        } elseif (!in_array((int)$project['owner_id'], $userIds, true)) {
            $error = 'Виберіть користувача, який буде власником проєкту.';
        } else {
            if ($id) {
                $stmt = $conn->prepare('UPDATE projects SET name=?, description=?, owner_id=? WHERE id=?');
                $stmt->bind_param('ssii', $project['name'], $project['description'], $project['owner_id'], $id);
            } else {
                $stmt = $conn->prepare('INSERT INTO projects (name, description, owner_id) VALUES (?, ?, ?)');
                $stmt->bind_param('ssi', $project['name'], $project['description'], $project['owner_id']);
            }
            if ($stmt->execute()) {
                $savedId = $id ?: $stmt->insert_id;
                flash($id ? 'Проєкт оновлено.' : 'Проєкт створено.');
                redirect('../zavd3.php?id=' . $savedId);
            }
            $error = 'Не вдалося зберегти проєкт.';
        }
    }
}
require '../includes/header.php';
?>
<div class="breadcrumb"><a href="index.php">Адмінпанель</a><span>/</span><span><?= $id ? 'Редагування проєкту' : 'Новий проєкт' ?></span></div>
<section class="form-page"><div class="page-heading"><div class="eyebrow">КЕРУВАННЯ ПРОЄКТАМИ</div><h1><?= $id ? 'Редагувати проєкт' : 'Створити проєкт' ?></h1><p>Вкажіть назву, опис і користувача-власника.</p></div>
<div class="admin-form-card"><?php if ($error): ?><div class="notice error"><?= h($error) ?></div><?php endif; ?><?php if (!$users): ?><div class="notice error">Спочатку має бути створений хоча б один користувач.</div><?php endif; ?>
<form method="post" class="form-grid"><?php csrf_field(); ?><label class="full-field">Назва проєкту<input type="text" name="name" maxlength="150" required value="<?= h($project['name']) ?>"></label><label class="full-field">Опис<textarea name="description" rows="5"><?= h($project['description']) ?></textarea></label><label class="full-field">Власник проєкту<select name="owner_id" required><?php foreach ($users as $item): ?><option value="<?= (int)$item['id'] ?>" <?= (int)$project['owner_id'] === (int)$item['id'] ? 'selected' : '' ?>><?= h($item['name']) ?> (<?= h($item['email']) ?>)</option><?php endforeach; ?></select></label><div class="form-actions full-field"><button class="button" type="submit" <?= !$users ? 'disabled' : '' ?>><?= $id ? 'Зберегти зміни' : 'Створити проєкт' ?></button><a class="button button-light" href="index.php">Скасувати</a></div></form></div></section>
<?php require '../includes/footer.php'; ?>
