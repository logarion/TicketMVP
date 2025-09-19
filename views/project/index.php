<?php include __DIR__ . '/../partials/header.php'; ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Projects</h2>
        <a href="index.php?controller=project&action=create" class="btn btn-primary">New Project</a>
    </div>
    <?php if (!empty($projects)): ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($projects as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['id']) ?></td>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td><?= htmlspecialchars($p['status']) ?></td>
                    <td><?= htmlspecialchars($p['created_at']) ?></td>
                    <td>
                        <a href="index.php?controller=project&action=view&id=<?= $p['id'] ?>" class="btn btn-sm btn-info">View</a>
                        <a href="index.php?controller=project&action=edit&id=<?= $p['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="index.php?controller=project&action=delete&id=<?= $p['id'] ?>" 
                           onclick="return confirm('Delete this project?');" 
                           class="btn btn-sm btn-danger">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No projects found.</p>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>
