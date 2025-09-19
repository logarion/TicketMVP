<?php
// views/login.php
$title = 'Login';
ob_start();
?>
<div class="row justify-content-center">
  <div class="col-md-6">
    <h2>Login</h2>
    <?php if (!empty($_GET['registered'])): ?>
      <div class="alert alert-success">Registration successful. You can log in now.</div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="/?action=login">
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input name="email" type="email" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input name="password" type="password" class="form-control" required>
      </div>
      <button class="btn btn-primary">Login</button>
      <a href="/?action=register" class="btn btn-link">Register</a>
    </form>
  </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
