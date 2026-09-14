<?php
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: zavd1.php");
    exit;
}

$login = "admin";
$pass = "1234";

if (isset($_SESSION['user'])) {
    echo "Вже увійшов як: " . $_SESSION['user'];
    echo "<br><a href='?logout=1'>Вийти</a>";
    exit;
}

if ($_POST) {
    if ($_POST['login'] == $login && $_POST['pass'] == $pass) {
        $_SESSION['user'] = $login;
        header("Location: zavd1.php");
        exit;
    } else {
        echo "Невірний логін або пароль <br><br>";
    }
}
?>

<form method="post">
    Логін: <input name="login"><br><br>
    Пароль: <input name="pass" type="password"><br><br>
    <button>Увійти</button>
</form>
