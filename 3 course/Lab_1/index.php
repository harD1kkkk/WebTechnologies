<?php

require_once "db.php";

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
";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Task Manager</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h1>Список завдань</h1>

<table>
    <tr>
        <th>ID</th>
        <th>Завдання</th>
        <th>Опис</th>
        <th>Статус</th>
        <th>Пріоритет</th>
        <th>Користувач</th>
        <th>Проект</th>
        <th>Дата завершення</th>
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
            <td><?= $row['due_date'] ?></td>
        </tr>

    <?php endwhile; ?>

</table>

</body>
</html>
