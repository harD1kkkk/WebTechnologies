<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_login();
$pageTitle = 'Мої завдання';
$rootPrefix = '';
$user = current_user();
$stmt = $conn->prepare('SELECT t.*, p.name AS project_name, u.name AS user_name FROM tasks t LEFT JOIN projects p ON p.id=t.project_id LEFT JOIN users u ON u.id=t.user_id WHERE t.user_id=? ORDER BY FIELD(t.status, \'В процесі\', \'Нове\', \'Завершено\'), t.due_date ASC, t.id DESC');
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
require 'includes/header.php';
?>
<section class="page-heading"><div class="eyebrow">ОСОБИСТИЙ ПРОСТІР</div><h1>Мої завдання</h1><p>Завдання, у яких ти вказаний відповідальним користувачем.</p></section>
<?php require 'includes/task_cards.php'; ?>
<?php require 'includes/footer.php'; ?>
