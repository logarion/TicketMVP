<?php include __DIR__ . '/../partials/header.php'; ?>
<div class="container mt-4">
    <h2>Create Project</h2>
    <form method="post" action="index.php?controller=project&action=store">
        <div class="mb-3">
            <label for="name" class="form-label">Project Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="4"></textarea>
        </div>
        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select name="status" id="status" class="form-select">
                <option value="New">New</option>
                <option value="In Progress">In Progress</option>
                <option value="Completed">Completed</option>
            </select>
        </div>
        <button type="submit" class="btn btn-success">Save</button>
        <a href="index.php?controller=project&action=index" class="btn btn-secondary">Cancel</a>
    </form>
</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>
