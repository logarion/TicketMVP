<?php include __DIR__ . '/../shared/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Users</h3>
  <div>
    <a href="index.php?page=admin_departments" class="btn btn-outline-secondary me-2">Departments</a>
    <a href="index.php?page=admin_groups" class="btn btn-outline-secondary me-2">Groups</a>
    <a href="index.php?page=admin_user_create" class="btn btn-primary">Add User</a>
  </div>
</div>

<table class="table table-striped align-middle">
  <thead><tr>
    <th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Department</th><th>Actions</th>
  </tr></thead>
  <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?= $u['id'] ?></td>
        <td><?= htmlspecialchars($u['username']) ?></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td><span class="badge <?= $u['role']==='admin'?'bg-danger':'bg-secondary' ?>"><?= $u['role'] ?></span></td>
        <td><?= htmlspecialchars($u['department_name'] ?? '—') ?></td>
        <td>
          <a class="btn btn-sm btn-outline-primary" href="index.php?page=admin_user_edit&id=<?= $u['id'] ?>">Edit</a>
          <a class="btn btn-sm btn-outline-warning" href="index.php?page=admin_user_reset_pw&id=<?= $u['id'] ?>">Reset Password</a>
          <a class="btn btn-sm btn-outline-info" href="index.php?page=admin_user_memberships&id=<?= $u['id'] ?>">Dept/Groups</a>
          <a class="btn btn-sm btn-outline-danger" href="index.php?page=admin_user_delete&id=<?= $u['id'] ?>" onclick="return confirm('Delete user?');">Delete</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php include __DIR__ . '/../shared/footer.php'; ?>
