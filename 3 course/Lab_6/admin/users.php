<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_admin();
$pageTitle = 'Користувачі';
$error = '';
$currentUser = current_user();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
    $role = (string)($_POST['role'] ?? 'user');
    if (!csrf_is_valid()) {
        $error = 'Форма застаріла. Оновіть сторінку та спробуйте ще раз.';
    } elseif (!$id || !in_array($role, ['user', 'admin'], true)) {
        $error = 'Некоректні дані користувача.';
    } elseif ((int)$currentUser['id'] === (int)$id && $role !== 'admin') {
        $error = 'Не можна зняти адміністративні права з власного облікового запису.';
    } else {
        $stmt = $conn->prepare('UPDATE users SET role=? WHERE id=?');
        $stmt->bind_param('si', $role, $id);
        if ($stmt->execute()) {
            flash('Роль користувача оновлено.');
            redirect('users.php');
        }
        $error = 'Не вдалося оновити роль.';
    }
}
$users = $conn->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC, id DESC')->fetch_all(MYSQLI_ASSOC);
require '../includes/header.php';
?>
<section class="page-heading"><div class="eyebrow">ДОСТУП ДО СИСТЕМИ</div><h1>Користувачі</h1><p>Переглядай облікові записи й керуй ролями.</p></section>
<?php if ($error): ?><div class="notice error"><?= h($error) ?></div><?php endif; ?>
<div class="table-wrap"><table class="data-table"><thead><tr><th>Ім’я</th><th>Електронна пошта</th><th>Дата реєстрації</th><th>Роль</th><th>Дія</th></tr></thead><tbody>
<?php foreach ($users as $item): ?><tr><td><strong><?= h($item['name']) ?></strong></td><td><?= h($item['email']) ?></td><td><?= h(format_date($item['created_at'])) ?></td><td><span class="role-pill <?= $item['role'] === 'admin' ? 'role-admin' : '' ?>"><?= $item['role'] === 'admin' ? 'Адміністратор' : 'Користувач' ?></span></td><td><form method="post" class="role-form"><?php csrf_field(); ?><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><select name="role"><option value="user" <?= $item['role'] !== 'admin' ? 'selected' : '' ?>>Користувач</option><option value="admin" <?= $item['role'] === 'admin' ? 'selected' : '' ?>>Адміністратор</option></select><button class="button button-small" type="submit">Зберегти</button></form></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php require '../includes/footer.php'; ?>
