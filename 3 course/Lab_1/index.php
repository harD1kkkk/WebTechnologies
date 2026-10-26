<?php
require_once "db.php";

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function showTasks($conn, $heading) {
    echo "<h3>" . h($heading) . "</h3>";

    $sql = "SELECT tasks.id, tasks.title, tasks.description, tasks.status,
                   tasks.priority, users.name AS user_name,
                   projects.name AS project_name, tasks.due_date
            FROM tasks
            INNER JOIN users ON tasks.user_id = users.id
            INNER JOIN projects ON tasks.project_id = projects.id
            ORDER BY tasks.id";
    $result = $conn->query($sql);

    if (!$result) {
        echo "<p>Помилка запиту: " . h($conn->error) . "</p>";
        return;
    }

    if ($result->num_rows === 0) {
        echo "<p>Записів немає.</p>";
        return;
    }

    echo "<table><tr>
            <th>ID</th><th>Назва</th><th>Опис</th><th>Статус</th>
            <th>Пріоритет</th><th>Відповідальний</th><th>Проєкт</th><th>Дата</th>
          </tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach (["id", "title", "description", "status", "priority", "user_name", "project_name", "due_date"] as $field) {
            echo "<td>" . h($row[$field] ?? "") . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    $result->free();
}

/* Прибираємо тестовий запис, який міг залишитися з попереднього запуску. */
$testTitle = "[LAB1-DEMO] Тестове завдання";
$stmt = $conn->prepare("DELETE FROM tasks WHERE title LIKE '[LAB1-DEMO]%'");
$stmt->execute();
$stmt->close();

$userResult = $conn->query("SELECT id FROM users ORDER BY id LIMIT 1");
$projectResult = $conn->query("SELECT id FROM projects ORDER BY id LIMIT 1");

if (!$userResult || !$projectResult ||
    $userResult->num_rows === 0 || $projectResult->num_rows === 0) {
    die("Для виконання лабораторної роботи додайте хоча б одного користувача та один проєкт у базу даних.");
}

$userId = (int)$userResult->fetch_assoc()["id"];
$projectId = (int)$projectResult->fetch_assoc()["id"];
$description = "Тестовий запис для демонстрації SQL-запитів.";
$status = "Нове";
$priority = "Низький";
$dueDate = "2026-12-31";
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Лабораторна робота 1</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #222; }
        h2 { margin-top: 28px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 22px; }
        th, td { border: 1px solid #999; padding: 6px; text-align: left; }
        th { background: #eee; }
    </style>
</head>
<body>
<h1>Лабораторна робота 1 — зміни в базі даних</h1>

<h2>Вивід 1. Початкові дані</h2>
<?php showTasks($conn, "Записи до виконання INSERT"); ?>

<?php
$insert = $conn->prepare(
    "INSERT INTO tasks (title, description, status, priority, user_id, project_id, due_date)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
$insert->bind_param("ssssiis", $testTitle, $description, $status, $priority, $userId, $projectId, $dueDate);
$insert->execute();
$testId = $conn->insert_id;
$insert->close();
?>

<h2>Вивід 2. Після додавання (INSERT)</h2>
<?php showTasks($conn, "Список після додавання тестового завдання"); ?>

<?php
$updatedTitle = "[LAB1-DEMO] Завдання після редагування";
$updatedDescription = "Дані змінено SQL-запитом UPDATE.";
$updatedStatus = "В процесі";
$updatedPriority = "Середній";
$update = $conn->prepare(
    "UPDATE tasks SET title = ?, description = ?, status = ?, priority = ? WHERE id = ?"
);
$update->bind_param("ssssi", $updatedTitle, $updatedDescription, $updatedStatus, $updatedPriority, $testId);
$update->execute();
$update->close();
?>

<h2>Вивід 3. Після редагування (UPDATE)</h2>
<?php showTasks($conn, "Список після зміни тестового завдання"); ?>

<?php
$delete = $conn->prepare("DELETE FROM tasks WHERE id = ?");
$delete->bind_param("i", $testId);
$delete->execute();
$delete->close();
?>

<h2>Вивід 4. Після видалення (DELETE)</h2>
<?php showTasks($conn, "Список після видалення тестового завдання"); ?>
</body>
</html>
<?php $conn->close(); ?>