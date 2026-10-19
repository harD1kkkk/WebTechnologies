<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/image_upload.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_is_valid()) {
    flash('Некоректний запит на видалення.', 'error');
    redirect('index.php');
}
$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    flash('Некоректний номер завдання.', 'error');
    redirect('index.php');
}
$stmt = $conn->prepare('SELECT image_path, thumbnail_path FROM tasks WHERE id=?');
$stmt->bind_param('i', $id);
$stmt->execute();
$image = $stmt->get_result()->fetch_assoc();
$stmt = $conn->prepare('DELETE FROM tasks WHERE id=?');
$stmt->bind_param('i', $id);
if ($stmt->execute() && $stmt->affected_rows > 0) {
    if ($image) {
        delete_task_images($image['image_path'], $image['thumbnail_path']);
    }
    flash('Завдання видалено.');
} else {
    flash('Не вдалося видалити завдання.', 'error');
}
redirect('index.php');
