<?php
$host = 'localhost';
$dbname = 'task_manager';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die('Не вдалося підключитися до бази task_manager. Перевірте, чи запущено MySQL та чи виконано database_upgrade.sql.');
}

$conn->set_charset('utf8mb4');
