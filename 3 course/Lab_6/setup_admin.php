<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$pageTitle = 'Створення адміністратора';
$error = '';
$result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role='admin'");
$adminExists = (int)$result->fetch_assoc()['total'] > 0;
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$adminExists) {
    if (!csrf_is_valid()) {
        $error = 'Форма застаріла. Оновіть сторінку та спробуйте ще раз.';
    } else {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['password_confirm'] ?? '');
        if ($name === '' || mb_strlen($name) > 100) {
            $error = 'Введіть ім’я адміністратора.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            $error = 'Введіть коректну електронну адресу.';
        } elseif (strlen($password) < 8) {
            $error = 'Пароль має містити щонайменше 8 символів.';
        } elseif ($password !== $confirm) {
            $error = 'Паролі не збігаються.';
        } else {
            $check = $conn->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
            $check->bind_param('s', $email);
            $check->execute();
            if ($check->get_result()->fetch_assoc()) {
                $error = 'Користувач із такою електронною адресою вже існує. Вкажіть іншу адресу.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $insert = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
                $insert->bind_param('sss', $name, $email, $hash);
                if ($insert->execute()) {
                    flash('Обліковий запис адміністратора створено. Тепер можна увійти.');
                    redirect('login.php');
                }
                $error = 'Не вдалося створити адміністратора.';
            }
        }
    }
}
require 'includes/header.php';
?>
<section class="auth-layout setup-layout"><div class="auth-aside"><div class="auth-mark">⚙</div><div class="eyebrow">ПЕРШИЙ ЗАПУСК</div><h1>Налаштування адміністратора.</h1></div><div class="auth-card"><div class="eyebrow">АДМІНІСТРАТОР</div><h2>Створити адміністратора</h2>
    <?php if ($adminExists): ?><div class="notice error">Адміністратор уже існує. Створення через цю сторінку вимкнено.</div><a class="button button-wide" href="login.php">Перейти до входу</a>
    <?php else: ?>
        <p class="muted">Заповніть форму.</p>
        <?php if ($error): ?><div class="notice error"><?= h($error) ?></div><?php endif; ?>
        <form method="post" class="form-stack"><?php csrf_field(); ?><label>Ім’я<input name="name" maxlength="100" value="<?= h($name) ?>" required></label><label>Електронна пошта<input type="email" name="email" maxlength="150" value="<?= h($email) ?>" required></label><label>Пароль<input type="password" name="password" minlength="8" required></label><label>Повторіть пароль<input type="password" name="password_confirm" minlength="8" required></label><button class="button button-wide" type="submit">Створити адміністратора →</button></form>
    <?php endif; ?>
</div></section>
<?php require 'includes/footer.php'; ?>
