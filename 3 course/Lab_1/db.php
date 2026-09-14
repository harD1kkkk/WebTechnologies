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
