<?php include __DIR__ . '/../shared/header.php'; ?>
<h3>Reset Password</h3>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post">
  <div class="mb-3">
    <label class="form-label">New Password</label>
    <input name="password" type="password" class="form-control" minlength="6" required>
  </div>
  <button class="btn btn-warning">Reset</button>
  <a href="index.php?page=admin_users" class="btn btn-secondary">Cancel</a>
</form>
<?php include __DIR__ . '/../shared/footer.php'; ?>
