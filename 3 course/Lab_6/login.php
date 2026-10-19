<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$pageTitle = 'Вхід';
$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid()) {
        $error = 'Форма застаріла. Оновіть сторінку та спробуйте ще раз.';
    } else {
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $stmt = $conn->prepare('SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        $passwordOk = false;
        $legacyPassword = false;
        if ($user && password_verify($password, $user['password'])) {
            $passwordOk = true;
        } elseif ($user && hash_equals((string)$user['password'], $password)) {
            $passwordOk = true;
            $legacyPassword = true;
        }

        if ($user && $passwordOk) {
            if ($legacyPassword) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $update = $conn->prepare('UPDATE users SET password=? WHERE id=?');
                $update->bind_param('si', $hash, $user['id']);
                $update->execute();
            }
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int)$user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'] ?? 'user'
            ];
            flash('Ви успішно увійшли в систему.');
            redirect('zavd1.php');
        } else {
            $error = 'Неправильна електронна пошта або пароль.';
        }
    }
}
require 'includes/header.php';
?>
<section class="auth-layout"><div class="auth-aside"><div class="auth-mark">✓</div><div class="eyebrow">РАДІ БАЧИТИ ЗНОВУ</div><h1>Тримай важливі справи під контролем.</h1><p>Увійди, щоб переглядати свої завдання, брати участь в обговореннях і працювати з проєктами.</p><div class="auth-benefit"><span>✓</span> Усе структуровано</div><div class="auth-benefit"><span>✓</span> Жодного зайвого хаосу</div></div>
    <div class="auth-card"><div class="eyebrow">ВІТАЄМО</div><h2>Вхід у систему</h2><p class="muted">Введи дані свого облікового запису.</p>
        <?php if ($error): ?><div class="notice error"><?= h($error) ?></div><?php endif; ?>
        <form method="post" class="form-stack"><?php csrf_field(); ?>
            <label>Електронна пошта<input type="email" name="email" value="<?= h($email) ?>" required autocomplete="email"></label>
            <label>Пароль<input type="password" name="password" required autocomplete="current-password"></label>
            <button class="button button-wide" type="submit">Увійти →</button>
        </form><p class="auth-switch">Ще немає акаунта? <a href="register.php">Зареєструватися</a></p>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
