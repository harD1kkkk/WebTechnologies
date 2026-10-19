<?php if (empty($tasks)): ?>
    <div class="empty-state"><div class="empty-icon">⌕</div><h3>Завдань поки немає</h3><p>Спробуйте змінити фільтри або додайте завдання в адмінпанелі.</p></div>
<?php else: ?>
    <div class="task-grid">
        <?php foreach ($tasks as $task): ?>
            <article class="task-card">
                <a class="task-thumb" href="<?= h($rootPrefix ?? '') ?>zavd4.php?id=<?= (int)$task['id'] ?>">
                    <?php if (!empty($task['thumbnail_path']) && is_file(dirname(__DIR__) . '/' . $task['thumbnail_path'])): ?>
                        <img src="<?= h($rootPrefix ?? '') ?><?= h($task['thumbnail_path']) ?>" alt="Фото завдання <?= h($task['title']) ?>">
                    <?php else: ?>
                        <img src="<?= h($rootPrefix ?? '') ?>assets/task-placeholder.svg" alt="Без фотографії">
                    <?php endif; ?>
                </a>
                <div class="task-card-body">
                    <div class="card-meta"><span class="status-pill <?= h(status_class($task['status'])) ?>"><?= h($task['status']) ?></span><span class="priority priority-<?= $task['priority'] === 'Високий' ? 'high' : ($task['priority'] === 'Низький' ? 'low' : 'medium') ?>"><?= h($task['priority']) ?> пріоритет</span></div>
                    <h3><a href="<?= h($rootPrefix ?? '') ?>zavd4.php?id=<?= (int)$task['id'] ?>"><?= h($task['title']) ?></a></h3>
                    <p class="task-description"><?= h(mb_strimwidth((string)($task['description'] ?? ''), 0, 125, '…', 'UTF-8')) ?></p>
                    <div class="task-info"><span>Проєкт: <?= h($task['project_name'] ?? 'Не вказано') ?></span><span>До: <?= h(format_date($task['due_date'] ?? null)) ?></span></div>
                    <div class="task-card-footer"><span class="avatar-small"><?= h(mb_strtoupper(mb_substr((string)($task['user_name'] ?? '?'), 0, 1, 'UTF-8'), 'UTF-8')) ?></span><span><?= h($task['user_name'] ?? 'Не призначено') ?></span><a class="arrow-link" href="<?= h($rootPrefix ?? '') ?>zavd4.php?id=<?= (int)$task['id'] ?>">Деталі ↗</a></div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
