<?php
$file = "messages.txt";

// запис даних у файл
if (isset($_POST['send'])) {
    $name = trim($_POST['name']);
    $message = trim($_POST['message']);

    if ($name !== "" && $message !== "") {
        $line = $name . " | " . $message . PHP_EOL;
        file_put_contents($file, $line, FILE_APPEND);
    }
}

// читання всіх записів з файлу
$messages = [];
if (file_exists($file)) {
    $messages = file($file, FILE_IGNORE_NEW_LINES);
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Lab 10 - Zavd1</title>
</head>
<body>

<h2>Форма введення</h2>

<form method="POST">
    Ім’я:<br>
    <input type="text" name="name"><br><br>

    Повідомлення:<br>
    <textarea name="message"></textarea><br><br>

    <button type="submit" name="send">Відправити</button>
</form>

<hr>

<h2>Всі записи</h2>

<?php if (!empty($messages)): ?>
    <ul>
        <?php foreach ($messages as $msg): ?>
            <li><?= htmlspecialchars($msg) ?></li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>Записів ще немає</p>
<?php endif; ?>

</body>
</html>
