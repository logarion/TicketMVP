<?php include __DIR__ . '/../partials/header.php'; ?>
<div class="container mt-4">
    <h2>Project: <?= htmlspecialchars($project['name']) ?></h2>
    <p><strong>Status:</strong> <?= htmlspecialchars($project['status']) ?></p>
    <p><?= nl2br(htmlspecialchars($project['description'])) ?></p>
    <p><strong>Created:</strong> <?= htmlspecialchars($project['created_at']) ?></p>

    <hr>
    <h4>Tasks</h4>
    <?php if (!empty($tasks)): ?>
        <ul class="list-group mb-3">
            <?php foreach ($tasks as $task): ?>
                <li class="list-group-item">
                    <strong><?= htmlspecialchars($task['title']) ?></strong>
                    <span class="badge bg-secondary"><?= htmlspecialchars($task['status']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No tasks for this project.</p>
    <?php endif; ?>

    <a href="index.php?controller=task&action=create&project_id=<?= $project['id'] ?>" class="btn btn-primary">Add Task</a>
    <a href="index.php?controller=project&action=index" class="btn btn-secondary">Back to Projects</a>
</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>
