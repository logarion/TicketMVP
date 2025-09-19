<?php include __DIR__ . '/../shared/header.php'; ?>
<h3><?= isset($user) ? 'Edit User' : 'Add User' ?></h3>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post">
  <div class="row g-3">
    <div class="col-md-4">
      <label class="form-label">Username</label>
      <input name="username" class="form-control" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">Email</label>
      <input name="email" type="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">Role</label>
      <select name="role" class="form-select">
        <option value="user"  <?= (($user['role'] ?? '')==='user')?'selected':'' ?>>user</option>
        <option value="admin" <?= (($user['role'] ?? '')==='admin')?'selected':'' ?>>admin</option>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Department</label>
      <select name="department_id" class="form-select">
        <option value="">(none)</option>
        <?php foreach ($departments as $d): ?>
          <option value="<?= $d['id'] ?>" <?= (isset($user['department_id']) && (string)$user['department_id']===(string)$d['id'])?'selected':'' ?>>
            <?= htmlspecialchars($d['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <?php if (!isset($user)): // only on create ?>
      <div class="col-md-4">
        <label class="form-label">Password</label>
        <input name="password" type="password" class="form-control" minlength="6" required>
      </div>
    <?php endif; ?>
  </div>
  <div class="mt-3">
    <button class="btn btn-primary"><?= isset($user) ? 'Save Changes' : 'Create User' ?></button>
    <a href="index.php?page=admin_users" class="btn btn-secondary">Cancel</a>
  </div>
</form>
<?php include __DIR__ . '/../shared/footer.php'; ?>
