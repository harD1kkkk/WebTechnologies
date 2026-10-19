<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$pageTitle = 'Головна';
$counts = [];
foreach (['tasks' => 'Завдання', 'projects' => 'Проєкти', 'users' => 'Користувачі', 'comments' => 'Коментарі'] as $table => $label) {
    $result = $conn->query("SELECT COUNT(*) AS total FROM {$table}");
    $counts[$label] = (int)$result->fetch_assoc()['total'];
}
$sql = "SELECT t.*, p.name AS project_name, u.name AS user_name
        FROM tasks t
        LEFT JOIN projects p ON p.id = t.project_id
        LEFT JOIN users u ON u.id = t.user_id
        ORDER BY t.created_at DESC, t.id DESC LIMIT 6";
$result = $conn->query($sql);
$tasks = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$rootPrefix = '';
require 'includes/header.php';
?>
<section class="hero">
    <div class="hero-copy">
        <div class="eyebrow"><span class="eyebrow-dot"></span> ПРОСТО КЕРУВАТИ СПРАВАМИ</div>
        <h1>Усі завдання.<br><span>Один зрозумілий простір.</span></h1>
        <p>Переглядайте проєкти, відстежуйте статуси й знаходьте потрібні завдання за кілька секунд.</p>
        <div class="hero-actions"><a class="button button-large" href="zavd2.php">Переглянути завдання <span>↗</span></a><a class="text-link" href="zavd3.php">Усі проєкти →</a></div>
        <div class="hero-note"><span class="checkmark">✓</span> Статуси, пріоритети та терміни в одному місці</div>
    </div>
    <div class="hero-visual" aria-hidden="true">
        <div class="dashboard-card dashboard-main">
            <div class="dashboard-top"><span class="dashboard-dot"></span><span class="dashboard-dot"></span><span class="dashboard-dot"></span><span class="dashboard-title">Огляд завдань</span></div>
            <div class="mini-progress-label"><span>Поточний прогрес</span><strong>72%</strong></div><div class="progress-track"><span style="width:72%"></span></div>
            <div class="mini-task"><span class="mini-check">✓</span><span><b>Підготовка проєкту</b><small>Дизайн та планування</small></span><em>Готово</em></div>
            <div class="mini-task"><span class="mini-check active">•</span><span><b>Розробка функціоналу</b><small>Основні можливості</small></span><em class="mini-active">В процесі</em></div>
            <div class="mini-task"><span class="mini-check muted">○</span><span><b>Фінальна перевірка</b><small>Тестування результату</small></span><em class="mini-muted">Нове</em></div>
        </div>
        <div class="floating-stat"><span>✓</span><div><b>Плануй</b><small>Виконуй вчасно</small></div></div>
    </div>
</section>
<section class="stats-grid">
    <?php foreach ($counts as $label => $value): ?>
        <div class="stat-card"><div class="stat-icon"><?= $label === 'Завдання' ? '▤' : ($label === 'Проєкти' ? '▦' : ($label === 'Користувачі' ? '♙' : '☷')) ?></div><div><strong><?= $value ?></strong><span><?= h($label) ?></span></div></div>
    <?php endforeach; ?>
</section>
<section class="section-block">
    <div class="section-heading"><div><div class="eyebrow">РОБОЧИЙ ПРОСТІР</div><h2>Останні завдання</h2><p>Свіжі записи з бази даних</p></div><a class="button button-outline" href="zavd2.php">Усі завдання <span>→</span></a></div>
    <?php require 'includes/task_cards.php'; ?>
</section>
<?php require 'includes/footer.php'; ?>
