<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$pageTitle = 'Реєстрація';
$error = '';
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid()) {
        $error = 'Форма застаріла. Оновіть сторінку та спробуйте ще раз.';
    } else {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $passwordConfirm = (string)($_POST['password_confirm'] ?? '');

        if ($name === '' || mb_strlen($name) > 100) {
            $error = 'Введіть ім’я (до 100 символів).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            $error = 'Введіть коректну електронну адресу.';
        } elseif (strlen($password) < 8) {
            $error = 'Пароль має містити щонайменше 8 символів.';
        } elseif ($password !== $passwordConfirm) {
            $error = 'Паролі не збігаються.';
        } else {
            $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()) {
                $error = 'Користувач із такою електронною адресою вже існує.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
                $stmt->bind_param('sss', $name, $email, $hash);
                if ($stmt->execute()) {
                    flash('Реєстрація успішна. Тепер увійдіть у систему.');
                    redirect('login.php');
                }
                $error = 'Не вдалося створити обліковий запис.';
            }
        }
    }
}
require 'includes/header.php';
?>
<section class="auth-layout"><div class="auth-aside"><div class="auth-mark">✓</div><div class="eyebrow">ПОЧНИ ЗАРАЗ</div><h1>Організуй роботу у своєму темпі.</h1><p>Створи обліковий запис, щоб переглядати призначені завдання та залишати коментарі.</p><div class="auth-benefit"><span>✓</span> Особистий список завдань</div><div class="auth-benefit"><span>✓</span> Коментарі та обговорення</div></div>
    <div class="auth-card"><div class="eyebrow">НОВИЙ ОБЛІКОВИЙ ЗАПИС</div><h2>Реєстрація</h2><p class="muted">Заповни поля нижче.</p>
        <?php if ($error): ?><div class="notice error"><?= h($error) ?></div><?php endif; ?>
        <form method="post" class="form-stack"><?php csrf_field(); ?>
            <label>Ім’я<input type="text" name="name" value="<?= h($name) ?>" maxlength="100" required autocomplete="name"></label>
            <label>Електронна пошта<input type="email" name="email" value="<?= h($email) ?>" maxlength="150" required autocomplete="email"></label>
            <label>Пароль<input type="password" name="password" minlength="8" required autocomplete="new-password"><small>Щонайменше 8 символів.</small></label>
            <label>Повторіть пароль<input type="password" name="password_confirm" minlength="8" required autocomplete="new-password"></label>
            <button class="button button-wide" type="submit">Створити обліковий запис →</button>
        </form><p class="auth-switch">Уже зареєстровані? <a href="login.php">Увійти</a></p>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
