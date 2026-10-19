<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): void
{
    echo '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function csrf_is_valid(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && is_string($_POST['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

function flash(?string $message = null, string $type = 'success')
{
    if ($message !== null) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
        return null;
    }

    if (!isset($_SESSION['flash_message'])) {
        return null;
    }

    $result = [
        'message' => $_SESSION['flash_message'],
        'type' => $_SESSION['flash_type'] ?? 'success'
    ];
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
    return $result;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        flash('Для перегляду цієї сторінки потрібно увійти в систему.', 'error');
        redirect('login.php');
    }
}

function require_admin(): void
{
    $user = current_user();
    if (!$user || ($user['role'] ?? 'user') !== 'admin') {
        http_response_code(403);
        echo '<!doctype html><html lang="uk"><meta charset="utf-8"><title>Доступ заборонено</title>';
        echo '<body style="font-family:Arial,sans-serif;padding:40px"><h1>Доступ заборонено</h1>';
        echo '<p>Ця сторінка доступна лише адміністратору.</p><a href="../zavd1.php">На головну</a></body></html>';
        exit;
    }
}

function bind_params(mysqli_stmt $stmt, string $types, array &$params): void
{
    if ($types === '' || count($params) === 0) {
        return;
    }

    $arguments = [$types];
    foreach ($params as $key => &$value) {
        $arguments[] = &$value;
    }
    call_user_func_array([$stmt, 'bind_param'], $arguments);
}

function format_date(?string $date): string
{
    if (!$date || $date === '0000-00-00') {
        return 'Не вказано';
    }
    $time = strtotime($date);
    return $time ? date('d.m.Y', $time) : h($date);
}

function status_class(string $status): string
{
    return match ($status) {
        'Завершено' => 'status-done',
        'В процесі' => 'status-progress',
        default => 'status-new'
    };
}
