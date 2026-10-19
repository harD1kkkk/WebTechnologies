<?php
require_once 'includes/functions.php';
unset($_SESSION['user']);
session_regenerate_id(true);
flash('Ви вийшли із системи.');
redirect('zavd1.php');
