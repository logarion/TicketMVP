<?php include __DIR__ . '/../shared/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Departments</h3>
  <a href="index.php?page=admin_users" class="btn btn-secondary">Back to Users</a>
</div>

<form method="post" class="row g-2 mb-3">
  <div class="col-md-6"><input name="name" class="form-control" placeholder="New department name" required></div>
  <div class="col-md-2"><button class="btn btn-primary w-100">Add</button></div>
</form>

<table class="table table-striped align-middle">
  <thead><tr><th>ID</th><th>Name</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach ($departments as $d): ?>
      <tr>
        <td><?= $d['id'] ?></td>
        <td><?= htmlspecialchars($d['name']) ?></td>
        <td>
          <form method="post" onsubmit="return confirm('Delete department?')" class="d-inline">
            <input type="hidden" name="delete_id" value="<?= $d['id'] ?>">
            <button class="btn btn-sm btn-outline-danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php include __DIR__ . '/../shared/footer.php'; ?>
