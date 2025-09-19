<?php include __DIR__ . '/../shared/header.php'; ?>
<h3>Edit Ticket #<?= $ticket['id'] ?></h3>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<form method="post" action="index.php?page=ticket_edit&id=<?= $ticket['id'] ?>">
  <div class="mb-3">
    <label class="form-label">Title</label>
    <input name="title" class="form-control" value="<?= htmlspecialchars($ticket['title']) ?>" required>
  </div>

  <div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description" class="form-control" rows="5"><?= htmlspecialchars($ticket['description'] ?? '') ?></textarea>
  </div>

  <div class="mb-3">
  <label class="form-label">Requester (email or username)</label>
  <?php
    $reqValue = $ticket['requester_username'] ?? $ticket['requester_email'] ?? '';
  ?>
  <input name="requester" class="form-control" value="<?= htmlspecialchars($reqValue) ?>">
</div>


  <div class="row g-3">
    <div class="col-md-4">
      <label class="form-label">Priority</label>
      <select name="priority" class="form-select">
        <option value="Normal" <?= $ticket['priority']==='Normal'?'selected':'' ?>>Normal</option>
        <option value="Urgent" <?= $ticket['priority']==='Urgent'?'selected':'' ?>>Urgent</option>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Assignee</label>
      <select name="assigned_to" class="form-select">
        <option value="">Unassigned</option>
        <?php foreach (($users ?? []) as $u): ?>
          <option value="<?= $u['id'] ?>" <?= (string)$ticket['assigned_to'] === (string)$u['id'] ? 'selected':'' ?>>
            <?= htmlspecialchars($u['username']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="mt-3">
    <button class="btn btn-primary">Save</button>
    <a class="btn btn-secondary" href="index.php?page=ticket_view&id=<?= $ticket['id'] ?>">Cancel</a>
  </div>
</form>

<?php include __DIR__ . '/../shared/footer.php'; ?>
