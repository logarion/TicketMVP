<?php include __DIR__ . '/../shared/header.php'; ?>
<h3>Project #<?= $project['id'] ?></h3>
<p><strong>Name:</strong> <?= htmlspecialchars($project['name']) ?></p>
<p><strong>From Ticket:</strong> <?= htmlspecialchars($project['ticket_title']) ?> (#<?= $project['ticket_id'] ?>)</p>
<p><strong>Created:</strong> <?= $project['created_at'] ?></p>
<hr>
<p><?= nl2br(htmlspecialchars($project['description'] ?? '')) ?></p>

<hr>
<div class="d-flex justify-content-between align-items-center mb-2">
  <h4 class="mb-0">Tasks</h4>
</div>

<!-- Inline Create Task Form 
<form class="card card-body mb-3" method="post" action="index.php?page=task_create">
  <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
  <div class="row g-2 align-items-end">
    <div class="col-md-4">
      <label class="form-label">Title</label>
      <input class="form-control" name="title" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">Description</label>
      <input class="form-control" name="description">
    </div>
    <div class="col-md-2">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option>Pending</option>
        <option>In Progress</option>
        <option>Done</option>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Due Date</label>
      <input type="date" name="due_date" class="form-control">
    </div>
  </div>
  <div class="mt-2">
    <button class="btn btn-success btn-sm">Add Task</button>
  </div>
</form>  -->

<!-- Inline Create Task Form -->
<form class="card card-body mb-3" method="post" action="index.php?page=task_create">
  <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
  <div class="row g-2 align-items-end">
    <div class="col-md-4">
      <label class="form-label">Title</label>
      <input class="form-control" name="title" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Assigned To</label>
      <select name="assigned_to" class="form-select">
        <option value="">Unassigned</option>
        <?php foreach ($users as $u): ?>
          <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option>Pending</option>
        <option>In Progress</option>
        <option>Done</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Due Date</label>
      <input type="date" name="due_date" class="form-control">
    </div>
  </div>
  <div class="mt-2">
    <label class="form-label">Description</label>
    <input class="form-control" name="description">
  </div>
  <div class="mt-2">
    <button class="btn btn-success btn-sm">Add Task</button>
  </div>
</form>


<?php
// Make sure $tasks is defined by router; if not, load it here
if (!isset($tasks)) {
  require_once __DIR__ . '/../../models/Task.php';
  $tasks = Task::listByProject((int)$project['id']);
}
?>

<?php if (empty($tasks)): ?>
  <p class="text-muted">No tasks yet.</p>
<?php else: ?>
  <table class="table table-striped">
    <thead>
      <tr><th>Title</th><th>Status</th><th>Assigned</th><th>Due</th><th>Actions</th></tr>
    </thead>
    <tbody>
      <?php foreach ($tasks as $t): ?>
      <tr>
        <td><?= htmlspecialchars($t['title']) ?></td>
        <td>
          <form method="post" action="index.php?page=task_update_status" class="d-inline">
            <input type="hidden" name="id" value="<?= $t['id'] ?>">
            <select name="status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
              <option value="Pending"     <?= $t['status']==='Pending'?'selected':'' ?>>Pending</option>
              <option value="In Progress" <?= $t['status']==='In Progress'?'selected':'' ?>>In Progress</option>
              <option value="Done"        <?= $t['status']==='Done'?'selected':'' ?>>Done</option>
            </select>
          </form>
        </td>
        <td><?= htmlspecialchars($t['assigned_username'] ?? '') ?></td>
        <td><?= htmlspecialchars($t['due_date'] ?? '') ?></td>
        <td>
          <a class="btn btn-sm btn-outline-secondary" href="index.php?page=task_edit&id=<?= $t['id'] ?>">Edit</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<a class="btn btn-secondary mt-3" href="index.php?page=projects_list">Back to Projects</a>
<?php include __DIR__ . '/../shared/footer.php'; ?>
