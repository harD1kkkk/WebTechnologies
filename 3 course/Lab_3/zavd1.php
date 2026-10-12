<?php
header('Content-Type: text/html; charset=utf-8');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = new mysqli('localhost', 'root', '', 'task_manager');
$conn->set_charset('utf8mb4');

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$errors = [];
$message = '';
$isEdit = false;
$id = 0;

$title = '';
$description = '';
$status = 'pending';
$priority = 'medium';
$user_id = '';
$project_id = '';
$due_date = '';

$statuses = [
    'pending' => 'Очікує',
    'in_progress' => 'У процесі',
    'completed' => 'Виконано'
];

$priorities = [
    'low' => 'Низький',
    'medium' => 'Середній',
    'high' => 'Високий'
];


$usersResult = $conn->query("SELECT id, name FROM users ORDER BY name");
$projectsResult = $conn->query("SELECT id, name FROM projects ORDER BY name");
$users = $usersResult->fetch_all(MYSQLI_ASSOC);
$projects = $projectsResult->fetch_all(MYSQLI_ASSOC);

if (isset($_GET['id']) || isset($_POST['id'])) {
    $id = filter_var($_POST['id'] ?? $_GET['id'], FILTER_VALIDATE_INT);

    if (!$id || $id < 1) {
        http_response_code(400);
        die('Некоректний ID завдання.');
    }

    $isEdit = true;

    $stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $task = $stmt->get_result()->fetch_assoc();

    if (!$task) {
        http_response_code(404);
        die('Завдання не знайдено.');
    }

    $title = $task['title'] ?? '';
    $description = $task['description'] ?? '';
    $status = $task['status'] ?? 'pending';
    $priority = $task['priority'] ?? 'medium';
    $user_id = $task['user_id'] ?? '';
    $project_id = $task['project_id'] ?? '';
    $due_date = $task['due_date'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? '';
    $priority = $_POST['priority'] ?? '';
    $user_id_raw = $_POST['user_id'] ?? '';
    $project_id_raw = $_POST['project_id'] ?? '';
    $due_date = trim($_POST['due_date'] ?? '');

    if ($title === '') {
        $errors['title'] = 'Введіть назву завдання.';
    } elseif (mb_strlen($title) > 255) {
        $errors['title'] = 'Назва не повинна перевищувати 255 символів.';
    }

    // Перевіряємо, чи статус і пріоритет належать до дозволених значень.
    if (!array_key_exists($status, $statuses)) {
        $errors['status'] = 'Оберіть правильний статус.';
    }

    if (!array_key_exists($priority, $priorities)) {
        $errors['priority'] = 'Оберіть правильний пріоритет.';
    }

    $user_id = filter_var($user_id_raw, FILTER_VALIDATE_INT);

    if (!$user_id || $user_id < 1) {
        $errors['user_id'] = 'Оберіть користувача.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        if (!$stmt->get_result()->fetch_assoc()) {
            $errors['user_id'] = 'Такого користувача не існує.';
        }
    }

    $project_id = filter_var($project_id_raw, FILTER_VALIDATE_INT);

    if (!$project_id || $project_id < 1) {
        $errors['project_id'] = 'Оберіть проєкт.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM projects WHERE id = ?");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();

        if (!$stmt->get_result()->fetch_assoc()) {
            $errors['project_id'] = 'Такого проєкту не існує.';
        }
    }

    if ($due_date !== '') {
        $date = DateTime::createFromFormat('!Y-m-d', $due_date);

        if (!$date || $date->format('Y-m-d') !== $due_date) {
            $errors['due_date'] = 'Введіть коректну дату.';
        }
    }

    if (count($errors) === 0) {
        $dateValue = ($due_date === '') ? null : $due_date;

        if ($isEdit) {
            $stmt = $conn->prepare(
                "UPDATE tasks
                 SET title = ?, description = ?, status = ?,
                     priority = ?, user_id = ?, project_id = ?,
                     due_date = ?
                 WHERE id = ?"
            );

            $stmt->bind_param(
                "ssssiisi",
                $title,
                $description,
                $status,
                $priority,
                $user_id,
                $project_id,
                $dateValue,
                $id
            );
            $stmt->execute();
            $message = 'Завдання успішно оновлено!';
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO tasks
                 (title, description, status, priority,
                  user_id, project_id, due_date)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssssiis",
                $title,
                $description,
                $status,
                $priority,
                $user_id,
                $project_id,
                $dateValue
            );
            $stmt->execute();
            $message = 'Завдання успішно додано!';
        }
    } else {
        $message = 'Виправте помилки у формі.';
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Керування завданнями</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 760px;
            margin: 30px auto;
            padding: 20px;
            background: #f3f5f7;
            color: #222;
        }

        h1 {
            font-size: 26px;
        }

        form {
            background: #fff;
            padding: 24px;
            border-radius: 10px;
            box-shadow: 0 2px 10px #00000012;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        input[type="text"], input[type="date"], textarea, select {
            width: 100%;
            box-sizing: border-box;
            padding: 10px;
            margin-top: 6px;
            border: 1px solid #bbb;
            border-radius: 5px;
            font: inherit;
        }

        .radio-option {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin: 8px 18px 0 0;
            font-weight: normal;
        }

        .radio-option input {
            width: auto;
        }

        .invalid {
            border: 2px solid #c62828 !important;
            background: #fff7f7;
        }

        .error {
            color: #b71c1c;
            font-size: 14px;
            margin-top: 5px;
        }

        .message {
            padding: 12px 14px;
            background: #e8eef5;
            border-left: 4px solid #476b91;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        button {
            margin-top: 22px;
            padding: 11px 18px;
            border: 0;
            border-radius: 5px;
            background: #245b8f;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #19446d;
        }

        .back-link {
            display: inline-block;
            margin-top: 18px;
        }
    </style>
</head>
<body>

<h1><?= $isEdit ? 'Редагування завдання' : 'Додавання завдання' ?></h1>

<?php if ($message !== ''): ?>
    <div class="message"><?= e($message) ?></div>
<?php endif; ?>

<form method="post" action="">
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= (int)$id ?>">
    <?php endif; ?>

    <label for="title">Назва завдання *</label>
    <input type="text" id="title" name="title"
           class="<?= isset($errors['title']) ? 'invalid' : '' ?>"
           value="<?= e($title) ?>">
    <?php if (isset($errors['title'])): ?>
        <div class="error"><?= e($errors['title']) ?></div>
    <?php endif; ?>

    <label for="description">Опис</label>
    <textarea id="description" name="description" rows="4"><?= e($description) ?></textarea>

    <label for="status">Статус *</label>
    <select id="status" name="status"
            class="<?= isset($errors['status']) ? 'invalid' : '' ?>">
        <?php foreach ($statuses as $key => $value): ?>
            <option value="<?= e($key) ?>"
                <?= $status === $key ? 'selected' : '' ?>>
                <?= e($value) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php if (isset($errors['status'])): ?>
        <div class="error"><?= e($errors['status']) ?></div>
    <?php endif; ?>

    <label>Пріоритет *</label>
    <?php foreach ($priorities as $key => $value): ?>
        <label class="radio-option">
            <input type="radio" name="priority" value="<?= e($key) ?>"
                <?= $priority === $key ? 'checked' : '' ?>>
            <?= e($value) ?>
        </label>
    <?php endforeach; ?>
    <?php if (isset($errors['priority'])): ?>
        <div class="error"><?= e($errors['priority']) ?></div>
    <?php endif; ?>

    <label for="user_id">Користувач *</label>
    <select id="user_id" name="user_id"
            class="<?= isset($errors['user_id']) ? 'invalid' : '' ?>">
        <option value="">Оберіть користувача</option>
        <?php foreach ($users as $user): ?>
            <option value="<?= (int)$user['id'] ?>"
                <?= (string)$user_id === (string)$user['id'] ? 'selected' : '' ?>>
                <?= e($user['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php if (isset($errors['user_id'])): ?>
        <div class="error"><?= e($errors['user_id']) ?></div>
    <?php endif; ?>

    <label for="project_id">Проєкт *</label>
    <select id="project_id" name="project_id"
            class="<?= isset($errors['project_id']) ? 'invalid' : '' ?>">
        <option value="">Оберіть проєкт</option>
        <?php foreach ($projects as $project): ?>
            <option value="<?= (int)$project['id'] ?>"
                <?= (string)$project_id === (string)$project['id'] ? 'selected' : '' ?>>
                <?= e($project['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php if (isset($errors['project_id'])): ?>
        <div class="error"><?= e($errors['project_id']) ?></div>
    <?php endif; ?>

    <label for="due_date">Кінцевий термін</label>
    <input type="date" id="due_date" name="due_date"
           class="<?= isset($errors['due_date']) ? 'invalid' : '' ?>"
           value="<?= e($due_date) ?>">
    <?php if (isset($errors['due_date'])): ?>
        <div class="error"><?= e($errors['due_date']) ?></div>
    <?php endif; ?>

    <button type="submit">
        <?= $isEdit ? 'Зберегти зміни' : 'Додати завдання' ?>
    </button>
</form>

<p><a class="back-link" href="../Lab_1/index.php">Повернутися до головної сторінки</a></p>

</body>
</html>
