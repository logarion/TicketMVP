<?php include __DIR__ . '/../shared/header.php'; ?>
<h3>Edit Task #<?= $task['id'] ?></h3>
<form method="post" action="index.php?page=task_update">
  <input type="hidden" name="id" value="<?= $task['id'] ?>">
  <div class="mb-3">
    <label class="form-label">Title</label>
    <input class="form-control" name="title" value="<?= htmlspecialchars($task['title']) ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Description</label>
    <textarea class="form-control" name="description" rows="4"><?= htmlspecialchars($task['description'] ?? '') ?></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">Status</label>
    <select name="status" class="form-select">
      <option <?= $task['status']==='Pending'?'selected':'' ?>>Pending</option>
      <option <?= $task['status']==='In Progress'?'selected':'' ?>>In Progress</option>
      <option <?= $task['status']==='Done'?'selected':'' ?>>Done</option>
    </select>
  </div>
  <!---<div class="mb-3">
    <label class="form-label">Assigned To (User ID)</label>
    <input class="form-control" name="assigned_to" value="<?= htmlspecialchars($task['assigned_to'] ?? '') ?>">
  </div> ---> 
  <div class="mb-3">
  <label class="form-label">Assigned To</label>
  <select name="assigned_to" class="form-select">
    <option value="">Unassigned</option>
    <?php foreach ($users as $u): ?>
      <option value="<?= $u['id'] ?>" <?= (string)$u['id'] === (string)($task['assigned_to'] ?? '') ? 'selected' : '' ?>>
        <?= htmlspecialchars($u['username']) ?>
      </option>
    <?php endforeach; ?>
  </select>
 </div
  <div class="mb-3">
    <label class="form-label">Due Date</label>
    <input type="date" class="form-control" name="due_date" value="<?= htmlspecialchars($task['due_date'] ?? '') ?>">
  </div>
  <button class="btn btn-primary">Save</button>
  <a class="btn btn-secondary" href="index.php?page=project_view&id=<?= $task['project_id'] ?>">Cancel</a>
</form>
<?php include __DIR__ . '/../shared/footer.php'; ?>
