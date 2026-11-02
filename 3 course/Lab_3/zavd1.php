<?php
declare(strict_types=1);

session_start();
header('Content-Type: text/html; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Параметри локальної бази даних XAMPP.
const DB_HOST = 'localhost';
const DB_USER = 'root';
const DB_PASSWORD = '';
const DB_NAME = 'task_manager';

function h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function posted(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function validId(mixed $value): ?int
{
    if (!is_string($value) && !is_int($value)) {
        return null;
    }
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? null : $id;
}

function tableColumns(mysqli $db, string $table): array
{
    // Назви таблиць передаються лише з константного переліку нижче.
    $result = $db->query('SHOW COLUMNS FROM `' . $table . '`');
    $columns = [];
    while ($column = $result->fetch_assoc()) {
        $columns[$column['Field']] = $column;
    }
    return $columns;
}

function labelColumn(array $columns, array $candidates): string
{
    foreach ($candidates as $candidate) {
        if (isset($columns[$candidate])) {
            return $candidate;
        }
    }
    throw new RuntimeException('Не знайдено поле з назвою користувача або проєкту.');
}

function enumValues(array $columns, string $field, array $fallback): array
{
    if (!isset($columns[$field])) {
        throw new RuntimeException('У таблиці tasks немає поля ' . $field . '.');
    }
    $type = $columns[$field]['Type'];
    if (preg_match('/^enum\((.*)\)$/i', $type, $match) === 1) {
        return str_getcsv($match[1], ',', "'", '\\');
    }
    return $fallback;
}

function optionTitle(string $value): string
{
    $labels = [
        'pending' => 'Очікує', 'todo' => 'До виконання', 'new' => 'Нове',
        'in_progress' => 'У процесі', 'in progress' => 'У процесі',
        'completed' => 'Виконано', 'done' => 'Виконано',
        'cancelled' => 'Скасовано', 'canceled' => 'Скасовано',
        'low' => 'Низький', 'medium' => 'Середній', 'high' => 'Високий',
        'urgent' => 'Терміновий',
    ];
    return $labels[$value] ?? $value;
}

function fetchOptions(mysqli $db, string $table, string $labelField): array
{
    // labelField отримано лише зі списку реальних колонок SHOW COLUMNS.
    $result = $db->query("SELECT `id`, `$labelField` AS label FROM `$table` ORDER BY label, id");
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[(int)$row['id']] = (string)$row['label'];
    }
    return $items;
}

function getTask(mysqli $db, int $id): ?array
{
    $stmt = $db->prepare('SELECT id, title, description, status, priority, user_id, project_id, due_date FROM tasks WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $task = $stmt->get_result()->fetch_assoc();
    return $task ?: null;
}

if (empty($_SESSION['lab3_csrf'])) {
    $_SESSION['lab3_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['lab3_csrf'];

try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    $db->set_charset('utf8mb4');

    $taskColumns = tableColumns($db, 'tasks');
    $userColumns = tableColumns($db, 'users');
    $projectColumns = tableColumns($db, 'projects');
    $userLabel = labelColumn($userColumns, ['name', 'full_name', 'username', 'email']);
    $projectLabel = labelColumn($projectColumns, ['name', 'title', 'project_name']);
    $users = fetchOptions($db, 'users', $userLabel);
    $projects = fetchOptions($db, 'projects', $projectLabel);
    $statuses = enumValues($taskColumns, 'status', ['pending', 'in_progress', 'completed']);
    $priorities = enumValues($taskColumns, 'priority', ['low', 'medium', 'high']);
} catch (Throwable $error) {
    http_response_code(500);
    error_log('Lab_3 database setup error: ' . $error->getMessage());
    exit('<!doctype html><html lang="uk"><meta charset="utf-8"><title>Помилка підключення</title><body style="font:16px Arial;margin:40px"><h1>Не вдалося відкрити базу даних</h1><p>Перевір запуск MySQL у XAMPP, назву бази task_manager і наявність таблиць users, projects, tasks.</p></body></html>');
}

$action = isset($_GET['action']) && is_string($_GET['action']) ? $_GET['action'] : 'list';
$errors = [];
$form = [
    'title' => '', 'description' => '', 'status' => $statuses[0] ?? '',
    'priority' => $priorities[0] ?? '', 'user_id' => '',
    'project_id' => '', 'due_date' => '',
];
$editId = null;
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrfToken, posted('csrf_token'))) {
        http_response_code(403);
        exit('Термін дії форми завершився. Оновіть сторінку й повторіть дію.');
    }

    $mode = posted('mode');
    if ($mode === 'delete') {
        $deleteId = validId(posted('id'));
        if ($deleteId === null) {
            http_response_code(400);
            exit('Некоректний ID завдання.');
        }
        try {
            $stmt = $db->prepare('DELETE FROM tasks WHERE id = ?');
            $stmt->bind_param('i', $deleteId);
            $stmt->execute();
            $_SESSION['lab3_notice'] = $stmt->affected_rows > 0
                ? 'Завдання видалено.' : 'Завдання вже не існує.';
            header('Location: zavd1.php', true, 303);
            exit;
        } catch (mysqli_sql_exception $error) {
            error_log('Lab_3 deletion error: ' . $error->getMessage());
            $notice = 'Завдання не вдалося видалити. Можливо, на нього посилаються інші записи.';
            $action = 'list';
        }
    } elseif ($mode === 'save') {
        $editId = posted('id') === '' ? null : validId(posted('id'));
        if (posted('id') !== '' && $editId === null) {
            http_response_code(400);
            exit('Некоректний ID завдання.');
        }
        if ($editId !== null && getTask($db, $editId) === null) {
            http_response_code(404);
            exit('Завдання для редагування не знайдено.');
        }
        $action = $editId === null ? 'new' : 'edit';
        foreach (array_keys($form) as $field) {
            $form[$field] = posted($field);
        }

        $titleLength = function_exists('mb_strlen')
            ? mb_strlen($form['title'], 'UTF-8') : strlen($form['title']);
        if ($form['title'] === '') {
            $errors['title'] = 'Введіть назву завдання.';
        } elseif ($titleLength > 255) {
            $errors['title'] = 'Максимальна довжина назви — 255 символів.';
        }
        if (strlen($form['description']) > 60000) {
            $errors['description'] = 'Опис завдання занадто довгий.';
        }
        if (!in_array($form['status'], $statuses, true)) {
            $errors['status'] = 'Оберіть статус зі списку.';
        }
        if (!in_array($form['priority'], $priorities, true)) {
            $errors['priority'] = 'Оберіть пріоритет зі списку.';
        }

        $userId = validId($form['user_id']);
        if ($userId === null || !array_key_exists($userId, $users)) {
            $errors['user_id'] = 'Оберіть наявного користувача.';
        }
        $projectId = validId($form['project_id']);
        if ($projectId === null || !array_key_exists($projectId, $projects)) {
            $errors['project_id'] = 'Оберіть наявний проєкт.';
        }

        if ($form['due_date'] !== '') {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $form['due_date']);
            if ($date === false || $date->format('Y-m-d') !== $form['due_date']) {
                $errors['due_date'] = 'Введіть коректну дату.';
            }
        }

        if ($errors === []) {
            $dateValue = $form['due_date'] === '' ? null : $form['due_date'];
            $descriptionValue = $form['description'];
            try {
                if ($editId !== null) {
                    $stmt = $db->prepare('UPDATE tasks SET title=?, description=?, status=?, priority=?, user_id=?, project_id=?, due_date=? WHERE id=?');
                    $stmt->bind_param('ssssiisi', $form['title'], $descriptionValue, $form['status'], $form['priority'], $userId, $projectId, $dateValue, $editId);
                } else {
                    $stmt = $db->prepare('INSERT INTO tasks (title, description, status, priority, user_id, project_id, due_date) VALUES (?, ?, ?, ?, ?, ?, ?)');
                    $stmt->bind_param('ssssiis', $form['title'], $descriptionValue, $form['status'], $form['priority'], $userId, $projectId, $dateValue);
                }
                $stmt->execute();
                $_SESSION['lab3_notice'] = $editId !== null ? 'Зміни збережено.' : 'Завдання додано.';
                header('Location: zavd1.php', true, 303);
                exit;
            } catch (mysqli_sql_exception $error) {
                error_log('Lab_3 saving error: ' . $error->getMessage());
                $notice = 'Не вдалося зберегти завдання. Перевір структуру таблиці tasks і значення полів.';
            }
        }
    } else {
        http_response_code(400);
        exit('Невідома дія.');
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $action === 'edit') {
    $editId = validId($_GET['id'] ?? null);
    if ($editId === null || ($task = getTask($db, $editId)) === null) {
        http_response_code(404);
        exit('Завдання не знайдено. <a href="zavd1.php">Повернутися до списку</a>');
    }
    foreach ($form as $key => $_) {
        $form[$key] = (string)($task[$key] ?? '');
    }
}

if (!in_array($action, ['list', 'new', 'edit'], true)) {
    $action = 'list';
}
if ($action === 'list') {
    if (isset($_SESSION['lab3_notice'])) {
        $notice = (string)$_SESSION['lab3_notice'];
        unset($_SESSION['lab3_notice']);
    }
    $sql = "SELECT t.id, t.title, t.status, t.priority, t.due_date,
                   u.`$userLabel` AS user_label, p.`$projectLabel` AS project_label
            FROM tasks t
            LEFT JOIN users u ON u.id = t.user_id
            LEFT JOIN projects p ON p.id = t.project_id
            ORDER BY t.id DESC";
    try {
        $rows = $db->query($sql)->fetch_all(MYSQLI_ASSOC);
    } catch (mysqli_sql_exception $error) {
        error_log('Lab_3 list error: ' . $error->getMessage());
        $rows = [];
        $notice = 'Не вдалося завантажити список завдань. Перевір структуру таблиці tasks.';
    }
}
?>
<!doctype html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Керування завданнями</title>
    <style>
        :root { color-scheme: light; --bg:#f3f6fb; --ink:#17233a; --muted:#64748b; --line:#dce4ee; --accent:#2563eb; --red:#b42318; }
        * { box-sizing: border-box; }
        body { margin:0; background:var(--bg); color:var(--ink); font-family:system-ui,-apple-system,"Segoe UI",Arial,sans-serif; }
        .wrap { width:min(1100px,calc(100% - 32px)); margin:48px auto; }
        .top { display:flex; justify-content:space-between; align-items:center; gap:18px; margin-bottom:24px; }
        h1 { margin:0 0 7px; font-size:30px; letter-spacing:-.7px; }
        .subtitle { color:var(--muted); margin:0; }
        .card { background:#fff; border:1px solid var(--line); border-radius:16px; padding:22px; box-shadow:0 10px 30px rgba(22,38,63,.04); }
        .btn { display:inline-flex; align-items:center; justify-content:center; gap:7px; padding:10px 15px; border-radius:9px; border:1px solid var(--line); background:#fff; color:var(--ink); font-weight:650; font-size:14px; text-decoration:none; cursor:pointer; font-family:inherit; }
        .btn:hover { filter:brightness(.96); }
        .primary { color:#fff; background:var(--accent); border-color:var(--accent); }
        .danger { color:var(--red); border-color:#f4b4af; background:#fff8f7; }
        .small { padding:7px 10px; font-size:13px; }
        table { width:100%; border-collapse:collapse; font-size:14px; }
        th { text-align:left; font-size:12px; color:var(--muted); font-weight:750; letter-spacing:.04em; text-transform:uppercase; }
        th,td { padding:15px 11px; border-bottom:1px solid var(--line); }
        tbody tr:last-child td { border-bottom:0; }
        .actions { display:flex; gap:8px; align-items:center; white-space:nowrap; }
        .actions form { margin:0; }
        .task-title { font-weight:650; }
        .pill { display:inline-block; border-radius:30px; background:#eef3ff; color:#2b4a96; padding:5px 9px; font-size:12px; }
        .notice { border-radius:10px; padding:13px 16px; margin-bottom:20px; background:#e6f4ea; color:#20603b; }
        .notice.warning { background:#fff1ee; color:var(--red); }
        .empty { text-align:center; padding:48px 16px; color:var(--muted); }
        .form-card { max-width:730px; }
        .field { margin:0 0 19px; }
        .field > label, fieldset legend { display:block; margin-bottom:7px; font-weight:650; font-size:14px; }
        input[type=text],input[type=date],textarea,select { width:100%; border:1px solid #cbd5e1; border-radius:9px; padding:11px 12px; font:inherit; background:#fff; color:var(--ink); }
        textarea { min-height:104px; resize:vertical; }
        :is(input,textarea,select):focus-visible { outline:3px solid #bfdbfe; border-color:var(--accent); }
        .invalid { border:2px solid var(--red)!important; }
        .error { margin-top:5px; font-size:13px; color:var(--red); }
        .grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
        fieldset { margin:0; padding:0; border:0; }
        .radios { display:flex; flex-wrap:wrap; gap:16px; padding:9px 0; }
        .radios label { display:flex; align-items:center; gap:7px; cursor:pointer; }
        .footer-actions { display:flex; flex-wrap:wrap; gap:10px; margin-top:8px; }
        .table-scroll { overflow-x:auto; }
        .muted { color:var(--muted); }
        @media(max-width:720px) { .wrap{margin:26px auto;} .top{align-items:flex-start;flex-direction:column;} .grid{grid-template-columns:1fr;gap:0;} .card{padding:16px;} h1{font-size:26px;} }
    </style>
</head>
<body>
<main class="wrap">
    <?php if ($action === 'list'): ?>
        <div class="top">
            <div><h1>Завдання</h1><p class="subtitle">Перегляд, додавання, редагування та видалення записів</p></div>
            <a class="btn primary" href="?action=new">+ Додати завдання</a>
        </div>
        <?php if ($notice !== ''): ?><div class="notice <?= str_contains($notice, 'Не вдалося') ? 'warning' : '' ?>" role="status"><?= h($notice) ?></div><?php endif; ?>
        <section class="card table-scroll">
            <?php if ($rows === []): ?>
                <div class="empty">Поки немає завдань. Натисніть «Додати завдання».</div>
            <?php else: ?>
                <table>
                    <thead><tr><th>ID</th><th>Назва</th><th>Статус</th><th>Пріоритет</th><th>Користувач</th><th>Проєкт</th><th>Термін</th><th>Дії</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td class="muted"><?= h($row['id']) ?></td>
                            <td class="task-title"><?= h($row['title']) ?></td>
                            <td><span class="pill"><?= h(optionTitle((string)$row['status'])) ?></span></td>
                            <td><?= h(optionTitle((string)$row['priority'])) ?></td>
                            <td><?= h($row['user_label'] ?? '—') ?></td>
                            <td><?= h($row['project_label'] ?? '—') ?></td>
                            <td><?= h($row['due_date'] ?? '—') ?></td>
                            <td>
                                <div class="actions">
                                    <a class="btn small" href="?action=edit&amp;id=<?= (int)$row['id'] ?>">Змінити</a>
                                    <form method="post" onsubmit="return confirm('Видалити це завдання? Дію неможливо скасувати.');">
                                        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                        <input type="hidden" name="mode" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                        <button class="btn small danger" type="submit">Видалити</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <div class="top">
            <div><h1><?= $action === 'edit' ? 'Редагування завдання' : 'Нове завдання' ?></h1><p class="subtitle">Заповніть поля й збережіть запис</p></div>
            <a class="btn" href="zavd1.php">← До списку</a>
        </div>
        <?php if ($notice !== ''): ?><div class="notice warning" role="alert"><?= h($notice) ?></div><?php endif; ?>
        <?php if ($errors !== []): ?><div class="notice warning" role="alert">Перевірте виділені поля та виправте помилки.</div><?php endif; ?>
        <form class="card form-card" method="post" action="zavd1.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
            <input type="hidden" name="mode" value="save">
            <input type="hidden" name="id" value="<?= h($editId ?? '') ?>">
            <div class="field">
                <label for="title">Назва завдання *</label>
                <input id="title" type="text" name="title" maxlength="255" value="<?= h($form['title']) ?>" class="<?= isset($errors['title']) ? 'invalid' : '' ?>" aria-invalid="<?= isset($errors['title']) ? 'true' : 'false' ?>">
                <?php if (isset($errors['title'])): ?><div class="error"><?= h($errors['title']) ?></div><?php endif; ?>
            </div>
            <div class="field">
                <label for="description">Опис</label>
                <textarea id="description" name="description" class="<?= isset($errors['description']) ? 'invalid' : '' ?>"><?= h($form['description']) ?></textarea>
                <?php if (isset($errors['description'])): ?><div class="error"><?= h($errors['description']) ?></div><?php endif; ?>
            </div>
            <div class="grid">
                <div class="field">
                    <label for="status">Статус *</label>
                    <select id="status" name="status" class="<?= isset($errors['status']) ? 'invalid' : '' ?>">
                        <?php foreach ($statuses as $status): ?><option value="<?= h($status) ?>" <?= $form['status'] === $status ? 'selected' : '' ?>><?= h(optionTitle($status)) ?></option><?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['status'])): ?><div class="error"><?= h($errors['status']) ?></div><?php endif; ?>
                </div>
                <div class="field">
                    <fieldset><legend>Пріоритет *</legend>
                        <div class="radios">
                        <?php foreach ($priorities as $priority): ?>
                            <label><input type="radio" name="priority" value="<?= h($priority) ?>" <?= $form['priority'] === $priority ? 'checked' : '' ?>><?= h(optionTitle($priority)) ?></label>
                        <?php endforeach; ?>
                        </div>
                    </fieldset>
                    <?php if (isset($errors['priority'])): ?><div class="error"><?= h($errors['priority']) ?></div><?php endif; ?>
                </div>
            </div>
            <div class="grid">
                <div class="field">
                    <label for="user_id">Користувач *</label>
                    <select id="user_id" name="user_id" class="<?= isset($errors['user_id']) ? 'invalid' : '' ?>">
                        <option value="">Оберіть користувача</option>
                        <?php foreach ($users as $userId => $userName): ?><option value="<?= $userId ?>" <?= $form['user_id'] === (string)$userId ? 'selected' : '' ?>><?= h($userName) ?></option><?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['user_id'])): ?><div class="error"><?= h($errors['user_id']) ?></div><?php endif; ?>
                </div>
                <div class="field">
                    <label for="project_id">Проєкт *</label>
                    <select id="project_id" name="project_id" class="<?= isset($errors['project_id']) ? 'invalid' : '' ?>">
                        <option value="">Оберіть проєкт</option>
                        <?php foreach ($projects as $projectId => $projectName): ?><option value="<?= $projectId ?>" <?= $form['project_id'] === (string)$projectId ? 'selected' : '' ?>><?= h($projectName) ?></option><?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['project_id'])): ?><div class="error"><?= h($errors['project_id']) ?></div><?php endif; ?>
                </div>
            </div>
            <div class="field">
                <label for="due_date">Кінцевий термін</label>
                <input id="due_date" type="date" name="due_date" value="<?= h($form['due_date']) ?>" class="<?= isset($errors['due_date']) ? 'invalid' : '' ?>">
                <?php if (isset($errors['due_date'])): ?><div class="error"><?= h($errors['due_date']) ?></div><?php endif; ?>
            </div>
            <div class="footer-actions"><button class="btn primary" type="submit">Зберегти завдання</button><a class="btn" href="zavd1.php">Скасувати</a></div>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
