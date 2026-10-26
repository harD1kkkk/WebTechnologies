<?php
require_once "../Lab_1/db.php";

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function getValue($name) {
    return trim((string)($_GET[$name] ?? ""));
}

$filters = [
    "id_from" => getValue("id_from"),
    "id_to" => getValue("id_to"),
    "title" => getValue("title"),
    "description" => getValue("description"),
    "status" => getValue("status"),
    "priority" => getValue("priority"),
    "user_id" => getValue("user_id"),
    "user_name" => getValue("user_name"),
    "project_id" => getValue("project_id"),
    "project_name" => getValue("project_name"),
    "tag_id" => getValue("tag_id"),
    "comment" => getValue("comment"),
    "due_from" => getValue("due_from"),
    "due_to" => getValue("due_to"),
    "created_from" => getValue("created_from"),
    "created_to" => getValue("created_to")
];

$conditions = [];
$params = [];

if ($filters["id_from"] !== "") { $conditions[] = "tasks.id >= ?"; $params[] = $filters["id_from"]; }
if ($filters["id_to"] !== "") { $conditions[] = "tasks.id <= ?"; $params[] = $filters["id_to"]; }
if ($filters["title"] !== "") { $conditions[] = "tasks.title LIKE ?"; $params[] = "%" . $filters["title"] . "%"; }
if ($filters["description"] !== "") { $conditions[] = "tasks.description LIKE ?"; $params[] = "%" . $filters["description"] . "%"; }
if ($filters["status"] !== "") { $conditions[] = "tasks.status = ?"; $params[] = $filters["status"]; }
if ($filters["priority"] !== "") { $conditions[] = "tasks.priority = ?"; $params[] = $filters["priority"]; }
if ($filters["user_id"] !== "") { $conditions[] = "tasks.user_id = ?"; $params[] = $filters["user_id"]; }
if ($filters["user_name"] !== "") { $conditions[] = "users.name LIKE ?"; $params[] = "%" . $filters["user_name"] . "%"; }
if ($filters["project_id"] !== "") { $conditions[] = "tasks.project_id = ?"; $params[] = $filters["project_id"]; }
if ($filters["project_name"] !== "") { $conditions[] = "projects.name LIKE ?"; $params[] = "%" . $filters["project_name"] . "%"; }

if ($filters["tag_id"] !== "") {
    $conditions[] = "EXISTS (
        SELECT 1 FROM task_tags tt
        INNER JOIN tags tg ON tg.id = tt.tag_id
        WHERE tt.task_id = tasks.id AND tg.id = ?
    )";
    $params[] = $filters["tag_id"];
}
if ($filters["comment"] !== "") {
    $conditions[] = "EXISTS (
        SELECT 1 FROM comments c
        WHERE c.task_id = tasks.id AND c.comment LIKE ?
    )";
    $params[] = "%" . $filters["comment"] . "%";
}
if ($filters["due_from"] !== "") { $conditions[] = "tasks.due_date >= ?"; $params[] = $filters["due_from"]; }
if ($filters["due_to"] !== "") { $conditions[] = "tasks.due_date <= ?"; $params[] = $filters["due_to"]; }
if ($filters["created_from"] !== "") { $conditions[] = "DATE(tasks.created_at) >= ?"; $params[] = $filters["created_from"]; }
if ($filters["created_to"] !== "") { $conditions[] = "DATE(tasks.created_at) <= ?"; $params[] = $filters["created_to"]; }

$where = count($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

$sortOptions = [
    "id" => "tasks.id",
    "title" => "tasks.title",
    "status" => "tasks.status",
    "priority" => "tasks.priority",
    "user_name" => "users.name",
    "project_name" => "projects.name",
    "due_date" => "tasks.due_date",
    "created_at" => "tasks.created_at",
    "tag_names" => "tag_names"
];

$sort1 = getValue("sort1");
$sort2 = getValue("sort2");
$dir1 = strtoupper(getValue("dir1"));
$dir2 = strtoupper(getValue("dir2"));
if (!isset($sortOptions[$sort1])) $sort1 = "id";
if (!isset($sortOptions[$sort2])) $sort2 = "title";
if (!in_array($dir1, ["ASC", "DESC"], true)) $dir1 = "ASC";
if (!in_array($dir2, ["ASC", "DESC"], true)) $dir2 = "ASC";

$sql = "SELECT tasks.id, tasks.title, tasks.description, tasks.status, tasks.priority,
               tasks.due_date, tasks.created_at, users.name AS user_name,
               projects.name AS project_name,
               (SELECT GROUP_CONCAT(t.name ORDER BY t.name SEPARATOR ', ')
                FROM task_tags tt2 INNER JOIN tags t ON t.id = tt2.tag_id
                WHERE tt2.task_id = tasks.id) AS tag_names,
               (SELECT COUNT(*) FROM comments c2 WHERE c2.task_id = tasks.id) AS comment_count
        FROM tasks
        INNER JOIN users ON users.id = tasks.user_id
        INNER JOIN projects ON projects.id = tasks.project_id
        $where
        ORDER BY " . $sortOptions[$sort1] . " " . $dir1 . ", " .
                 $sortOptions[$sort2] . " " . $dir2;

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Помилка підготовки SQL-запиту: " . h($conn->error));
}
if (count($params) > 0) {
    $types = str_repeat("s", count($params));
    $bindParams = [$types];
    foreach ($params as $key => $value) {
        $bindParams[] = &$params[$key];
    }
    call_user_func_array([$stmt, "bind_param"], $bindParams);
}
$stmt->execute();
$result = $stmt->get_result();

$users = $conn->query("SELECT id, name FROM users ORDER BY name");
$projects = $conn->query("SELECT id, name FROM projects ORDER BY name");
$tags = $conn->query("SELECT id, name FROM tags ORDER BY name");
?>
<!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<title>Лабораторна робота 2 — фільтрування</title>
<style>
body { font-family: Arial, sans-serif; margin: 20px; color: #222; }
fieldset { margin-bottom: 14px; padding: 12px; }
.grid { display: grid; grid-template-columns: repeat(4, minmax(170px, 1fr)); gap: 10px; }
label { display: flex; flex-direction: column; gap: 4px; font-size: 14px; }
input, select, button { padding: 6px; }
button { margin-top: 10px; cursor: pointer; }
table { border-collapse: collapse; width: 100%; margin-top: 18px; }
th, td { border: 1px solid #999; padding: 6px; text-align: left; }
th { background: #eee; }
@media (max-width: 900px) { .grid { grid-template-columns: repeat(2, minmax(150px, 1fr)); } }
</style>
</head>
<body>
<h1>Лабораторна робота 2 — фільтрування та сортування завдань</h1>
<form method="get">
    <fieldset>
        <legend>Параметри завдання</legend>
        <div class="grid">
            <label>ID від<input type="number" name="id_from" value="<?= h($filters["id_from"]) ?>"></label>
            <label>ID до<input type="number" name="id_to" value="<?= h($filters["id_to"]) ?>"></label>
            <label>Назва містить<input name="title" value="<?= h($filters["title"]) ?>"></label>
            <label>Опис містить<input name="description" value="<?= h($filters["description"]) ?>"></label>
            <label>Статус
                <select name="status">
                    <option value="">Усі</option>
                    <?php foreach (["Нове", "В процесі", "Завершено"] as $value): ?>
                    <option value="<?= h($value) ?>" <?= $filters["status"] === $value ? "selected" : "" ?>><?= h($value) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Пріоритет
                <select name="priority">
                    <option value="">Усі</option>
                    <?php foreach (["Низький", "Середній", "Високий"] as $value): ?>
                    <option value="<?= h($value) ?>" <?= $filters["priority"] === $value ? "selected" : "" ?>><?= h($value) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Відповідальний користувач
                <select name="user_id">
                    <option value="">Усі</option>
                    <?php if ($users) { while ($u = $users->fetch_assoc()): ?>
                    <option value="<?= h($u["id"]) ?>" <?= $filters["user_id"] === (string)$u["id"] ? "selected" : "" ?>><?= h($u["name"]) ?></option>
                    <?php endwhile; } ?>
                </select>
            </label>
            <label>Ім’я користувача містить<input name="user_name" value="<?= h($filters["user_name"]) ?>"></label>
            <label>Проєкт
                <select name="project_id">
                    <option value="">Усі</option>
                    <?php if ($projects) { while ($p = $projects->fetch_assoc()): ?>
                    <option value="<?= h($p["id"]) ?>" <?= $filters["project_id"] === (string)$p["id"] ? "selected" : "" ?>><?= h($p["name"]) ?></option>
                    <?php endwhile; } ?>
                </select>
            </label>
            <label>Назва проєкту містить<input name="project_name" value="<?= h($filters["project_name"]) ?>"></label>
            <label>Тег
                <select name="tag_id">
                    <option value="">Усі</option>
                    <?php if ($tags) { while ($t = $tags->fetch_assoc()): ?>
                    <option value="<?= h($t["id"]) ?>" <?= $filters["tag_id"] === (string)$t["id"] ? "selected" : "" ?>><?= h($t["name"]) ?></option>
                    <?php endwhile; } ?>
                </select>
            </label>
            <label>Коментар містить<input name="comment" value="<?= h($filters["comment"]) ?>"></label>
            <label>Дата виконання від<input type="date" name="due_from" value="<?= h($filters["due_from"]) ?>"></label>
            <label>Дата виконання до<input type="date" name="due_to" value="<?= h($filters["due_to"]) ?>"></label>
            <label>Створено від<input type="date" name="created_from" value="<?= h($filters["created_from"]) ?>"></label>
            <label>Створено до<input type="date" name="created_to" value="<?= h($filters["created_to"]) ?>"></label>
        </div>
    </fieldset>
    <fieldset>
        <legend>Сортування за двома показниками</legend>
        <div class="grid">
            <label>Перше поле
                <select name="sort1">
                    <?php foreach ($sortOptions as $key => $value): ?>
                    <option value="<?= h($key) ?>" <?= $sort1 === $key ? "selected" : "" ?>><?= h($key) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Напрямок
                <select name="dir1">
                    <option value="ASC" <?= $dir1 === "ASC" ? "selected" : "" ?>>За зростанням</option>
                    <option value="DESC" <?= $dir1 === "DESC" ? "selected" : "" ?>>За спаданням</option>
                </select>
            </label>
            <label>Друге поле
                <select name="sort2">
                    <?php foreach ($sortOptions as $key => $value): ?>
                    <option value="<?= h($key) ?>" <?= $sort2 === $key ? "selected" : "" ?>><?= h($key) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Напрямок
                <select name="dir2">
                    <option value="ASC" <?= $dir2 === "ASC" ? "selected" : "" ?>>За зростанням</option>
                    <option value="DESC" <?= $dir2 === "DESC" ? "selected" : "" ?>>За спаданням</option>
                </select>
            </label>
        </div>
    </fieldset>
    <button type="submit">Застосувати фільтри</button>
    <a href="zavd1.php">Скинути</a>
</form>

<p>Знайдено записів: <?= (int)$result->num_rows ?></p>
<table>
<tr>
    <th>ID</th><th>Назва</th><th>Опис</th><th>Статус</th><th>Пріоритет</th>
    <th>Користувач</th><th>Проєкт</th><th>Теги</th><th>Коментарів</th><th>Дата виконання</th><th>Створено</th>
</tr>
<?php if ($result->num_rows === 0): ?>
<tr><td colspan="11">За вказаними умовами записів не знайдено.</td></tr>
<?php else: while ($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= h($row["id"]) ?></td>
    <td><?= h($row["title"]) ?></td>
    <td><?= h($row["description"]) ?></td>
    <td><?= h($row["status"]) ?></td>
    <td><?= h($row["priority"]) ?></td>
    <td><?= h($row["user_name"]) ?></td>
    <td><?= h($row["project_name"]) ?></td>
    <td><?= h($row["tag_names"] ?? "") ?></td>
    <td><?= h($row["comment_count"]) ?></td>
    <td><?= h($row["due_date"] ?? "") ?></td>
    <td><?= h($row["created_at"]) ?></td>
</tr>
<?php endwhile; endif; ?>
</table>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>