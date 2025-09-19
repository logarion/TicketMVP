<?php include __DIR__ . '/../shared/header.php'; ?>
<div class="row justify-content-center">
  <div class="col-md-5">
    <h3>Register</h3>
    <?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post" action="index.php?page=register">
      <div class="mb-3">
        <label class="form-label">Username</label>
        <input name="username" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input name="email" type="email" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input name="password" type="password" class="form-control" minlength="6" required>
      </div>
      <button class="btn btn-success">Create account</button>
      <a class="btn btn-link" href="index.php?page=login">Back to login</a>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../shared/footer.php'; ?>
