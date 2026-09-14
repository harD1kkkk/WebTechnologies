<?php

$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$gender = $_POST['gender'] ?? '';
$role = $_POST['role'] ?? '';
$newsletter = $_POST['newsletter'] ?? null;
$about = $_POST['about'] ?? '';
$token = $_POST['token'] ?? '';

$roles = [
    'user' => 'Користувач',
    'admin' => 'Адміністратор',
    'moderator' => 'Модератор'
];

$errors = [];

if (isset($_POST['submit'])) {

    // Ім’я
    if (!preg_match('/^[A-Za-zА-Яа-яІіЇїЄє]{2,20}$/u', $name)) {
        $errors['name'] = "Некоректне ім’я";
    }

    // Email
    if (!preg_match('/^[\w\.-]+@[\w\.-]+\.\w+$/', $email)) {
        $errors['email'] = "Некоректний email";
    }

    // Пароль
    if (!preg_match('/^.{6,}$/', $password)) {
        $errors['password'] = "Мінімум 6 символів";
    }

    // Стать
    if (!in_array($gender, ['m','f'])) {
        $errors['gender'] = "Оберіть стать";
    }

    // Роль
    if (!isset($roles[$role])) {
        $errors['role'] = "Оберіть роль";
    }

    if (count($errors) == 0) {

        $news = $newsletter ? "Так" : "Ні";

        echo "<h2>Дані успішно отримано!</h2>";
        echo "Ім’я: $name<br>";
        echo "Email: $email<br>";
        echo "Стать: " . ($gender == 'm' ? 'Чоловік' : 'Жінка') . "<br>";
        echo "Роль: {$roles[$role]}<br>";
        echo "Підписка: $news<br>";
        echo "Про себе: $about<br>";
        return;
    }
}
?>

<style>
.error { color: red; font-size: 12px; }
</style>

<form method="POST">

    Ім’я:<br>
    <input type="text" name="name" value="<?= $name ?>">
    <div class="error"><?= $errors['name'] ?? '' ?></div>

    <br>Email:<br>
    <input type="text" name="email" value="<?= $email ?>">
    <div class="error"><?= $errors['email'] ?? '' ?></div>

    <br>Пароль:<br>
    <input type="password" name="password">
    <div class="error"><?= $errors['password'] ?? '' ?></div>

    <br>Стать:<br>
    <input type="radio" name="gender" value="m" <?= $gender=='m'?'checked':'' ?>>Чоловік
    <input type="radio" name="gender" value="f" <?= $gender=='f'?'checked':'' ?>>Жінка
    <div class="error"><?= $errors['gender'] ?? '' ?></div>

    <br>Роль:<br>
    <select name="role">
        <option value="">--Виберіть--</option>
        <?php foreach($roles as $k => $v) { ?>
            <option value="<?= $k ?>" <?= $role==$k?'selected':'' ?>><?= $v ?></option>
        <?php } ?>
    </select>
    <div class="error"><?= $errors['role'] ?? '' ?></div>

    <br>
    <input type="checkbox" name="newsletter" value="1" <?= $newsletter?'checked':'' ?>>
    Підписка на новини

    <br><br>Про себе:<br>
    <textarea name="about"><?= $about ?></textarea>

    <!-- hidden -->
    <input type="hidden" name="token" value="abc123">

    <br><br>
    <input type="submit" name="submit" value="Відправити">

</form>
