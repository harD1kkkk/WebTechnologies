<?php

$host = "localhost";
$dbname = "task_manager";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Помилка підключення: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$title = $_GET['title'] ?? '';
$status = $_GET['status'] ?? '';
$priority = $_GET['priority'] ?? '';
$user_id = $_GET['user_id'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$sort1 = $_GET['sort1'] ?? 'title';
$order1 = $_GET['order1'] ?? 'ASC';

$sort2 = $_GET['sort2'] ?? 'due_date';
$order2 = $_GET['order2'] ?? 'ASC';

$allowedSorts = [
    'title' => 'tasks.title',
    'due_date' => 'tasks.due_date',
    'priority' => 'tasks.priority',
    'status' => 'tasks.status',
    'user_name' => 'users.name'
];

if (!isset($allowedSorts[$sort1])) {
    $sort1 = 'title';
}

if (!isset($allowedSorts[$sort2])) {
    $sort2 = 'due_date';
}

$order1 = $order1 === 'DESC' ? 'DESC' : 'ASC';
$order2 = $order2 === 'DESC' ? 'DESC' : 'ASC';

$sql = "
    SELECT
        tasks.id,
        tasks.title,
        tasks.description,
        tasks.status,
        tasks.priority,
        users.name AS user_name,
        projects.name AS project_name,
        tasks.due_date
    FROM tasks
    INNER JOIN users
        ON tasks.user_id = users.id
    INNER JOIN projects
        ON tasks.project_id = projects.id
    WHERE 1=1
";

$params = [];
$types = "";

if ($title !== '') {
    $sql .= " AND tasks.title LIKE ?";
    $params[] = "%$title%";
    $types .= "s";
}

if ($status !== '') {
    $sql .= " AND tasks.status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($priority !== '') {
    $sql .= " AND tasks.priority = ?";
    $params[] = $priority;
    $types .= "s";
}

if ($user_id !== '') {
    $sql .= " AND tasks.user_id = ?";
    $params[] = $user_id;
    $types .= "i";
}

if ($date_from !== '') {
    $sql .= " AND tasks.due_date >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if ($date_to !== '') {
    $sql .= " AND tasks.due_date <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$sql .= " ORDER BY {$allowedSorts[$sort1]} $order1, {$allowedSorts[$sort2]} $order2";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$result = $stmt->get_result();

$users = $conn->query("SELECT id, name FROM users ORDER BY name");
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Фільтрування та сортування завдань</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            background: #f5f5f5;
        }

        h1 {
            text-align: center;
        }

        form {
            background: white;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid #ccc;
        }

        .field {
            margin-bottom: 15px;
        }

        label {
            display: inline-block;
            width: 180px;
        }

        input,
        select {
            padding: 7px;
            width: 220px;
        }

        button {
            padding: 10px 20px;
            margin-right: 10px;
            cursor: pointer;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }

        th {
            background: #eeeeee;
        }
    </style>
</head>

<body>

<h1>Фільтрування та сортування завдань</h1>

<form method="GET">

    <div class="field">
        <label>Назва завдання:</label>
        <input type="text" name="title" value="<?= htmlspecialchars($title) ?>">
    </div>

    <div class="field">
        <label>Статус:</label>
        <select name="status">
            <option value="">Усі</option>
            <option value="Нове" <?= $status === 'Нове' ? 'selected' : '' ?>>Нове</option>
            <option value="В процесі" <?= $status === 'В процесі' ? 'selected' : '' ?>>В процесі</option>
            <option value="Завершено" <?= $status === 'Завершено' ? 'selected' : '' ?>>Завершено</option>
        </select>
    </div>

    <div class="field">
        <label>Пріоритет:</label>
        <select name="priority">
            <option value="">Усі</option>
            <option value="Низький" <?= $priority === 'Низький' ? 'selected' : '' ?>>Низький</option>
            <option value="Середній" <?= $priority === 'Середній' ? 'selected' : '' ?>>Середній</option>
            <option value="Високий" <?= $priority === 'Високий' ? 'selected' : '' ?>>Високий</option>
        </select>
    </div>

    <div class="field">
        <label>Користувач:</label>
        <select name="user_id">
            <option value="">Усі</option>

            <?php while ($user = $users->fetch_assoc()): ?>
                <option value="<?= $user['id'] ?>"
                    <?= $user_id == $user['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($user['name']) ?>
                </option>
            <?php endwhile; ?>

        </select>
    </div>

    <div class="field">
        <label>Дата від:</label>
        <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
    </div>

    <div class="field">
        <label>Дата до:</label>
        <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
    </div>

    <h3>Перше сортування</h3>

    <div class="field">
        <label>Поле:</label>

        <select name="sort1">
            <option value="title" <?= $sort1 === 'title' ? 'selected' : '' ?>>Назва</option>
            <option value="due_date" <?= $sort1 === 'due_date' ? 'selected' : '' ?>>Дата</option>
            <option value="priority" <?= $sort1 === 'priority' ? 'selected' : '' ?>>Пріоритет</option>
            <option value="status" <?= $sort1 === 'status' ? 'selected' : '' ?>>Статус</option>
            <option value="user_name" <?= $sort1 === 'user_name' ? 'selected' : '' ?>>Користувач</option>
        </select>
    </div>

    <div class="field">
        <label>Напрям:</label>

        <select name="order1">
            <option value="ASC" <?= $order1 === 'ASC' ? 'selected' : '' ?>>За зростанням</option>
            <option value="DESC" <?= $order1 === 'DESC' ? 'selected' : '' ?>>За спаданням</option>
        </select>
    </div>

    <h3>Друге сортування</h3>

    <div class="field">
        <label>Поле:</label>

        <select name="sort2">
            <option value="title" <?= $sort2 === 'title' ? 'selected' : '' ?>>Назва</option>
            <option value="due_date" <?= $sort2 === 'due_date' ? 'selected' : '' ?>>Дата</option>
            <option value="priority" <?= $sort2 === 'priority' ? 'selected' : '' ?>>Пріоритет</option>
            <option value="status" <?= $sort2 === 'status' ? 'selected' : '' ?>>Статус</option>
            <option value="user_name" <?= $sort2 === 'user_name' ? 'selected' : '' ?>>Користувач</option>
        </select>
    </div>

    <div class="field">
        <label>Напрям:</label>

        <select name="order2">
            <option value="ASC" <?= $order2 === 'ASC' ? 'selected' : '' ?>>За зростанням</option>
            <option value="DESC" <?= $order2 === 'DESC' ? 'selected' : '' ?>>За спаданням</option>
        </select>
    </div>

    <button type="submit">Застосувати</button>

    <a href="zavd1.php">
        <button type="button">Скинути</button>
    </a>

</form>

<table>

    <tr>
        <th>ID</th>
        <th>Завдання</th>
        <th>Опис</th>
        <th>Статус</th>
        <th>Пріоритет</th>
        <th>Користувач</th>
        <th>Проект</th>
        <th>Дата</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()): ?>

        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars($row['description']) ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
            <td><?= htmlspecialchars($row['priority']) ?></td>
            <td><?= htmlspecialchars($row['user_name']) ?></td>
            <td><?= htmlspecialchars($row['project_name']) ?></td>
            <td><?= htmlspecialchars($row['due_date']) ?></td>
        </tr>

    <?php endwhile; ?>

</table>

</body>
</html>
